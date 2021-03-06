<?php
namespace Bart\Git_Hook;

class Gerrit_Approved_Test extends TestBase
{
	private static $conf = array('gerrit' =>
		array('host' => 'gorgoroth.com', 'port' => '42')
	);
	private $w;

	public function setUp()
	{
		$this->w = new \Bart\Witness\Silent();
		parent::setUp();
	}

	public function test_valid_commit()
	{
		$change_id = 'Iabcde123';
		$commit_hash = 'abcde123';

		$mockApi = $this->getMock('\\Bart\\Gerrit\\Api', array(), array(), '', false);
		$mockApi->expects($this->once())
			->method('get_approved_change')
			->with($this->equalTo($change_id), $this->equalTo($commit_hash))
					// Just some non-null value
			->will($this->returnValue(array('id' => $change_id)));

		$this->configure_for($change_id, $commit_hash, $mockApi);

		$hook = new Gerrit_Approved(self::$conf, '.git', 'grinder', $this->w);
		$hook->verify($commit_hash);
	}

	public function test_change_not_found()
	{
		$change_id = 'Iabcde123';
		$commit_hash = 'abcde123';

		$mockApi = $this->getMock('\\Bart\\Gerrit\\Api', array(), array(), '', false);
		$mockApi->expects($this->once())
			->method('get_approved_change')
			->with($this->equalTo($change_id), $this->equalTo($commit_hash))
			->will($this->returnValue(null));

		$this->configure_for($change_id, $commit_hash, $mockApi);

		$hook = new Gerrit_Approved(self::$conf, '.git', 'grinder', $this->w);

		$msg = 'An approved review was not found in Gerrit for commit '
		. $commit_hash . ' with Change-Id ' . $change_id;
		$this->assertThrows('\Exception', $msg, function() use($hook, $commit_hash){
			$hook->verify($commit_hash);
		});
	}

	public function test_exception_in_gerrit()
	{
		$change_id = 'Iabcde123';
		$commit_hash = 'abcde123';

		$mockApi = $this->getMock('\\Bart\\Gerrit\\Api', array(), array(), '', false);
		$mockApi->expects($this->once())
			->method('get_approved_change')
			->with($this->equalTo($change_id), $this->equalTo($commit_hash))
			->will($this->throwException(new \Exception()));

		$this->configure_for($change_id, $commit_hash, $mockApi);

		$hook = new Gerrit_Approved(self::$conf, '.git', 'grinder', $this->w);

		$msg = 'Error getting Gerrit review info';
		$this->assertThrows('\Exception', $msg, function() use($hook, $commit_hash){
			$hook->verify($commit_hash);
		});
	}

	private function configure_for($change_id, $commit_hash, $mockApi)
	{
		$phpu = $this;
		$conf = self::$conf['gerrit'];
		$mock_git = $this->getGitStub();
		\Bart\Diesel::registerInstantiator('Bart\Gerrit\Api',
			function($gerritConf) use($phpu, $conf, $mockApi) {
				$phpu->assertEquals($conf, $gerritConf,
						'Expected params to contain gerrit conf');

				return $mockApi;
			});

		$mock_git->expects($this->once())
			->method('get_change_id')
			->with($this->equalTo($commit_hash))
			->will($this->returnValue($change_id));
	}
}
