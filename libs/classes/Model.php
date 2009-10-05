<?php

final class Model
{
	/**
	 * Returns the dom0 which has the id $id in the database or null.
	 *
	 * @param string $id             The identifier of the dom0.
	 * @param boolean $force_refresh The results are cached, pass true to ignore
	 *                               it.
	 *
	 * @return The dom0 if present, otherwise false.
	 */
	public static function get_dom0($id, $force_refresh = false)
	{
		if ($force_refresh)
		{
			$config = Config::get_instance();
			if (isset($config->$id))
			{
				$entries = $config->$id;

				list($address, $port) = explode (':', $id, 2);
				return self::$dom0s[$id] = new Dom0(
					$address,
					$port,
					isset($entries['username']) ? $entries['username'] : 'none',
					isset($entries['password']) ? $entries['password'] : 'none'
				);
			}
			// There is no such dom0.
			return self::$dom0s[$id] = false; // It may have existed.
		}

		if (isset(self::$dom0s, $id))
		{
			return (self::$dom0s[$id]);
		}
		if (self::$all_dom0s_retrieved)
		{
			return false;
		}

		// Not found but may exist, recall this method with $force_refresh set
		// to true.
		return self::get_dom0($id, true);
	}

	/**
	 * Returns all the dom0s present in the database.
	 *
	 * @param boolean $force_refresh The results are cached, pass true to ignore
	 *                               it.
	 *
	 * @return An array containing all the dom0 (can be empty).
	 */
	public static function get_dom0s($force_refresh = false)
	{
		if ($force_refresh || !self::$all_dom0s_retrieved)
		{
			$config = Config::get_instance();
			$dom0s = array(); // Necessary for the force refresh.
			self::$all_dom0s_retrieved = true;
			foreach ($config as $entry => $entries)
			{
				// Checks if this entry is a dom0.
				if (is_array($entries) && (strpos($entry, ':') !== false))
				{
					list($address, $port) = explode (':', $entry, 2);

					self::$dom0s[$entry] = new Dom0(
						$address,
						$port,
						isset($entries['username']) ? $entries['username'] : 'none',
						isset($entries['password']) ? $entries['password'] : 'none'
					);
				}
			}
		}
		return self::$dom0s;
	}

	/**
	 * Returns a reference to an array containing all the domUs of a dom0.
	 */
	public static function &get_domUs(Dom0 $dom0)
	{
		self::$domUs_by_dom0s[$dom0->id] = array();
		$xids = $dom0->rpc_query('VM.get_all');
		foreach ($xids as $xid)
		{
			// The domU Domain-0 is special, do not insert
			// it in the domUs array.
			if ($xid === '00000000-0000-0000-0000-000000000000')
			{
				continue;
			}

			$domU = new DomU($xid, $dom0);
			if (($domU->state === 'Halted')
				&& self::is_running_domU_named($domU->name))
			{
				continue;
			}

			if (($domU->state === 'Running') || ($domU->state === 'Paused'))
			{
				if (isset(self::$domUs_by_names[$domU->name]))
				{
					foreach (self::$domUs_by_names[$domU->name] as $dom0_id => $domU_)
					{
						if ($domU_->state === 'Halted')
						{
							unset (self::$domUs_by_dom0s[$dom0_id][$domU->name]);
							unset (self::$domUs_by_names[$domU->name][$dom0_id]);
						}
					}
				}
			}

			self::$domUs_by_dom0s[$dom0->id][$domU->name] = $domU;
			if (!isset(self::$domUs_by_names[$domU->name]))
			{
				self::$domUs_by_names[$domU->name] = array($dom0->id => $domU);
			}
			else
			{
				self::$domUs_by_names[$domU->name][$dom0->id] = $domU;
			}
		}

		return self::$domUs_by_dom0s[$dom0->id];
	}

	/**
	 * Returns the user who has the name $name if he exists, otherwise returns
	 * false.
	 *
	 * If $password is not null, the user's password will also be checked, if
	 * not correct, the function will return false.
	 *
	 * @param string $name          The user's name.
	 * @param string|null $password The user's password.
	 *
	 * @return The user or false.
	 */
	public static function get_user($name, $password = null)
	{
		$db = Database::get_instance();
		$sql = 'SELECT id, mail, permissions FROM users '
			. 'WHERE name = :name';

		if ($password === null)
		{
			$stmt = $db->prepare($sql);
		}
		else
		{
			$stmt = $db->prepare($sql . ' AND password = :password');
			$stmt->bindValue(':password', $password);
		}

		if (!$stmt->execute(array(':name' => $name)))
		{
			return false; // The request failed.
		}

		$r = $stmt->fetch(PDO::FETCH_NUM);
		return self::$users[$r[0]] = new User($r[0], $name, $r[1], $r[2]);
	}

	public static function get_user_acls(User $user)
	{
		$db = Database::get_instance();
		$stmt = $db->prepare('SELECT dom0_id, domU_name, permissions FROM acls '
			. 'WHERE user_id = :user_id');

		if (!$stmt->execute(array(':user_id' => $user->id)))
		{
			return array(); // The request failed.
		}

		$acls = array();
		while (($r = $stmt->fetch(PDO::FETCH_NUM)) !== false)
		{
			if (!isset($acls[$r[0]]))
			{
				$acls[$r[0]] = array();
			}

			if ($r[1] === null) // For the whole dom0.
			{
				$acls[$r[0]]['Domain-0'] = $r[2];
			}
			else
			{
				$acls[$r[0]][$r[1]] = $r[2];
			}
		}
		return $acls;
	}

	/**
	 * To avoid unecessary checking and object creation, all dom0s are stored in
	 * this array.
	 *
	 * @var array
	 */
	private static $dom0s = array();

	/**
	 * This flag equals true if all the dom0s are already retrieved, otherwise
	 * it equals false.
	 *
	 * @var boolean
	 */
	private static $all_dom0s_retrieved = false;

	/**
	 * This array contains all the domUs: dom0_ids => name => domU.
	 *
	 * @var array
	 */
	private static $domUs_by_dom0s = array();

	/**
	 * This array contains all the domUs:  names => dom0_id => domU.
	 *
	 * TODO: optimize get_domUs in inserting in this array only halted domUs.
	 *
	 * @var array
	 */
	private static $domUs_by_names = array();


/*
	private static $users = array();

	private static $all_users_retrieved = false;
*/

	/**
	 * Checks if there is a running domU with the name $name among the known
	 * domUs.
	 *
	 * @param string name
	 *
	 * @return True if there is at least one, otherwise false.
	 */
	private static function is_running_domU_named($name)
	{
		if (isset(self::$domUs_by_names[$name]))
		{
			foreach (self::$domUs_by_names[$name] as $domU)
			{
				if ($domU->state === 'Running')
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * This class cannot be instanciated.
	 */
	private function __construct()
	{}
}
