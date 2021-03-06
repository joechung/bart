<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Git;

class Base_Test extends \Bart\BaseTestCase
{
	public function testConstructor()
	{
		$conf = array();

		// mock git and method get_change_id to return $repo
		$mock_git = $this->getMock('\Bart\Git', array(), array(), '', false);
		$mock_git->expects($this->once())
				->method('get_change_id')
				->will($this->returnValue('grinder'));

		$phpu = $this;
		Diesel::registerInstantiator('Bart\Git',
			function($gitDir) use($mock_git, $phpu) {
				$phpu->assertEquals('.git', $gitDir,
						'Expected constructor to get git dir');

				return $mock_git;
		});

		$hook = new Test_Git_Hook($conf, '.git', 'grinder');
		$hook->verify($this);
	}
}

/*
 * Silly class to help us test that the base class will do its stuff
 */
class Test_Git_Hook extends Base
{
	public function __construct(array $hook_conf, $git_dir, $repo)
	{
		parent::__construct($hook_conf, $git_dir, $repo, new \Bart\Witness());
	}

	public function verify($phpu)
	{
		$phpu->assertNotNull($this->git,
				'Expected git to be defined by Base constructor');

		// Somewhat contrived -- make sure the mock git is used
		// ...and that $this->repo was set
		$phpu->assertEquals($this->repo, $this->git->get_change_id(''),
				'Expected mock git to be called');
	}
}
