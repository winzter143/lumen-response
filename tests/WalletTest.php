<?php
use F3\models\Wallet;

class WalletTest extends TestCase
{
    /**
     * Successful test scenario for Wallet::example().
     */
    public function testExampleSuccess()
    {
        // This will return true.
        $result = Wallet::example(true);

        // This test should pass.
        $this->assertTrue($result);
    }

    /**
     * Failed test scenario for Wallet::example().
     */
    public function testExampleFail()
    {
        // This will return true.
        $result = Wallet::example(false);

        // This test should fail.
        $this->assertTrue($result);
    }

}
