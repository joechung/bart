<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Witness;
use Bart\Config_Parser;

/**
 * Base of pre- and post-receive hooks
 */
class Receive_Runner_Base
{
	protected $git_dir;
	protected $repo;
	protected $hooks;
	protected $conf;
	protected $w;

	public function __construct($git_dir, $repo, Witness $w)
	{
		// Use the repo as the environment when parsing conf
		$parser = Diesel::create('Bart\Config_Parser', array($repo));
		$conf = $parser->parse_conf_file(BART_DIR . 'etc/php/hooks.conf');

		$this->git_dir = $git_dir;
		$this->repo = $repo;
		$this->hooks = explode(',', $conf[static::$type]['names']);
		$this->conf = $conf;
		$this->w = $w;
	}

	public function verify_all($commit_hash)
	{
		foreach ($this->hooks as $hook_name)
		{
			$hook = $this->create_hook_for($hook_name);

			if ($hook === null) continue;

			// Verify will throw exceptions on failure
			$hook->verify($commit_hash);
		}
	}

	/**
	 * Instantiate a new hook of type $hook_name
	 * Throws error or returns null if bad conf or disabled
	 */
	private function create_hook_for($hook_name)
	{
		if (!array_key_exists($hook_name, $this->conf))
		{
			throw new \Exception("No configuration section for hook $hook_name");
		}

		// All configurations for this hook
		$hook_conf = $this->conf[$hook_name];
		$class = 'Bart\\Git_Hook\\' . $hook_conf['class'];

		if (!class_exists($class))
		{
			throw new \Exception("Class for hook does not exist! ($class)");
		}

		if (!$hook_conf['enabled']) return null;

		$w = ($hook_conf['verbose']) ? new Witness() : $this->w;
		$w->report('...' . static::$type . ' verifying ' . $hook_name);

		return new $class($this->conf, $this->git_dir, $this->repo, $w);
	}
}
