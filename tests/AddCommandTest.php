<?php

namespace BrainMaestro\GitHooks\Tests;

use BrainMaestro\GitHooks\Commands\AddCommand;
use BrainMaestro\GitHooks\Hook;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AddCommandTester extends \PHPUnit_Framework_TestCase
{
    use PrepareHookTest;

    private $commandTester;

    public function setUp()
    {
        self::prepare();
        $command = new AddCommand(self::$hooks);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_adds_hooks_that_do_not_already_exist()
    {
        $this->commandTester->execute([]);

        foreach (array_keys(self::$hooks) as $hook) {
            $this->assertContains("Added '{$hook}' hook", $this->commandTester->getDisplay());
        }
    }

    /**
     * @test
     */
    public function it_does_not_add_hooks_that_already_exist()
    {
        foreach (self::$hooks as $hook => $script) {
            file_put_contents(".git/hooks/{$hook}", $script);
        }

        $this->commandTester->execute([]);

        foreach (array_keys(self::$hooks) as $hook) {
            $this->assertContains("'{$hook}' already exists", $this->commandTester->getDisplay());
        }
    }

    /**
     * @test
     */
    public function it_adds_hooks_that_already_exist_if_forced_to()
    {
        $hook = array_rand(self::$hooks);
        $script = self::$hooks[$hook];
        file_put_contents(".git/hooks/{$hook}", $script);

        $this->commandTester->execute(['--force' => true]);

        foreach (array_keys(self::$hooks) as $hook) {
            $this->assertContains("Added '{$hook}' hook", $this->commandTester->getDisplay());
        }
    }

    /**
     * @test
     */
    public function it_correctly_creates_the_hook_lock_file()
    {
        $this->commandTester->execute([]);

        $this->assertContains('Created '. Hook::LOCK_FILE . ' file', $this->commandTester->getDisplay());
        $this->assertTrue(file_exists(Hook::LOCK_FILE));
        $this->assertEquals(json_encode(array_keys(self::$hooks)), file_get_contents(Hook::LOCK_FILE));
    }

    /**
     * @test
     */
    public function it_does_not_create_the_hook_lock_file_if_the_no_lock_option_is_passed()
    {
        $this->commandTester->execute(['--no-lock' => true]);

        $this->assertContains('Skipped creating a '. Hook::LOCK_FILE . ' file', $this->commandTester->getDisplay());
        $this->assertFalse(file_exists(Hook::LOCK_FILE));
    }

    /**
     * @test
     */
    public function it_does_not_ignore_the_hook_lock_file()
    {
        $this->commandTester->execute([]);

        $this->assertContains('Skipped adding '. Hook::LOCK_FILE . ' to .gitignore', $this->commandTester->getDisplay());
        passthru('grep -q ' . Hook::LOCK_FILE . ' .gitignore', $return);
        $this->assertEquals(1, $return);
    }

    /**
     * @test
     */
    public function it_ignores_the_hook_lock_file_if_the_ignore_lock_option_is_passed()
    {
        $this->commandTester->execute(['--ignore-lock' => true]);

        $this->assertContains('Added ' . Hook::LOCK_FILE . ' to .gitignore', $this->commandTester->getDisplay());
        passthru('grep -q ' . Hook::LOCK_FILE . ' .gitignore', $return);
        $this->assertEquals(0, $return);
    }
}
