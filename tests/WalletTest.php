<?php
use F3\models\Wallet;
use F3\models\Party;

class WalletTest extends TestCase
{
    /**
     * Test accounts and wallets.
     */
    private static $org;
    private static $user;
        
    /**
     * Setup the users and wallets.  This needs to be static.
     */
    public static function setUpBeforeClass() 
    {
        // We need valid user fixtures for most of the tests.
        self::$user = Party::store('user');
        self::$org = Party::store('organization');
    }
    
    /**
     * Invalid party test.
     */
    public function testCreateInvalidPartyFail()
    {
        // Invalid party test.
        try {
            $w = Wallet::store(0, 'sales', 'PHP', 10000, 0);
        } catch (\Exception $e) {
            $error = $e->validator->getMessageBag()->first('party_id');
        }
        $this->assertContains('selected party id is invalid', $error);
    }

    /**
     * Invalid type test.
     */
    public function testCreateInvalidTypeFail()
    {
        try {
            $w = Wallet::store(self::$user->id, 'INVALID', 'PHP', 10000, 0);
        } catch (\Exception $e) {
            $error = $e->validator->getMessageBag()->first('type');
        }
        $this->assertContains('selected type is invalid', $error);
    }

    /**
     * Invalid currency test.
     * This test is simpler since it returns a standard Exception.
     *
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /currency code is invalid/
     */
    public function testCreateInvalidCurrencyFail()
    {
        $w = Wallet::store(self::$user->id, 'sales', 'INVALID', 10000, 0);
    }

    /**
     * Invalid max limit test.
     */
    public function testCreateInvalidMaxLimitFail()
    {
        try {
            $w = Wallet::store(self::$user->id, 'sales', 'PHP', -1000, 0);
        } catch (\Exception $e) {
            $error = $e->validator->getMessageBag()->first('max_limit');
        }
        $this->assertContains('max limit must be at least 0', $error);
    }

    /**
     * Invalid credit limit test.
     */
    public function testCreateInvalidCreditLimitFail()
    {
        try {
            $w = Wallet::store(self::$user->id, 'sales', 'PHP', 10000, 100);
        } catch (\Exception $e) {
            $error = $e->validator->getMessageBag()->first('credit_limit');
        }
        $this->assertContains('credit limit may not be greater than 0', $error);
    }

    /**
     * Successful org wallet creation.
     */
    public function testCreateOrgWalletOk()
    {
        $w = Wallet::store(self::$org->id, 'fund', 'PHP', 0, -10000);

        // Check the created wallet.
        $this->assertInstanceOf('F3\Models\Wallet', $w);
        $this->assertEquals('fund', $w->type);
        $this->assertEquals(113, $w->currency_id);
        $this->assertEquals(0, $w->max_limit);
        $this->assertEquals(-10000, $w->credit_limit);
        $this->assertEquals(0, $w->getBalance());

        // Fixture for other tests.
        return $w;
    }
    
    /**
     * Successful user wallet creation.
     */
    public function testCreateUserWalletOk()
    {
        $w = Wallet::store(self::$user->id, 'sales', 'PHP', 1000, 0);

        // Check the created wallet.
        $this->assertInstanceOf('F3\Models\Wallet', $w);
        $this->assertEquals('sales', $w->type);
        $this->assertEquals(113, $w->currency_id);
        $this->assertEquals(1000, $w->max_limit);
        $this->assertEquals(0, $w->credit_limit);
        $this->assertEquals(0, $w->getBalance());
        
        // Fixture for other tests.
        return $w;
    }

    /**
     * Invalid transfer amount.
     * 
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /should be greater than 0/
     */
    public function testTransferInvalidAmountFail()
    {
        Wallet::transfer(self::$org->id, self::$user->id, 'fund', 'sales', 'PHP', -100, 'transfer', 'test transfer');
    }

    /**
     * Invalid currency transfer test.
     *
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /currency code is invalid/
     */
    public function testTransferInvalidCurrencyFail()
    {
        Wallet::transfer(self::$org->id, self::$user->id, 'fund', 'sales', 'INVALID', 100, 'transfer', 'test transfer');
    }

    /**
     * Invalid source wallet.
     *
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /Cannot find source/
     */
    public function testTransferInvalidSrcWalletFail()
    {
        Wallet::transfer(0, self::$user->id, 'fund', 'sales', 'PHP', 100, 'transfer', 'test transfer');
    }

    /**
     * Invalid destination wallet.
     *
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /Cannot find destination/
     */
    public function testTransferInvalidDstWalletFail()
    {
        Wallet::transfer(self::$org->id, 0, 'fund', 'sales', 'PHP', 100, 'transfer', 'test transfer');
    }

    /**
     * Same wallet test.
     *
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /wallets must be different/
     */
    public function testTransferSameWalletFail()
    {
        Wallet::transfer(self::$org->id, self::$org->id, 'fund', 'fund', 'PHP', 100, 'transfer', 'test transfer');
    }

    /**
     * Insufficient funds test.
     *
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /Insufficient funds/
     */
    public function testTransferInsufficientFundsFail()
    {
        Wallet::transfer(self::$user->id, self::$org->id, 'sales', 'fund', 'PHP', 100, 'transfer', 'test transfer');
    }

    /**
     * Max limit exceeded test.
     *
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /Max limit exceeded/
     */
    public function testTransferMaxLimitExceededFail()
    {
        Wallet::transfer(self::$org->id, self::$user->id, 'fund', 'sales', 'PHP', 1001, 'transfer', 'test transfer');
    }

    /**
     * Successful transfer test.
     *
     * @depends testCreateOrgWalletOk
     * @depends testCreateUserWalletOk
     */
    public function testTransferOk($org_wallet, $user_wallet)
    {
        // Pick a random amount to transfer between 1.00 and 1000.00.
        $amount = rand(100, 100000)/100;
        Wallet::transfer(self::$org->id, self::$user->id, 'fund', 'sales', 'PHP', $amount, 'transfer', 'test transfer');
        
        // Check the wallet balances.
        $this->assertEquals(-$amount, $org_wallet->getBalance());
        $this->assertEquals($amount, $user_wallet->getBalance());
    }
    
    /**
     * Nuke the test data.
     */
    public static function tearDownAfterClass()
    {
    }
}
