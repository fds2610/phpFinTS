<?php

/** @noinspection PhpUnhandledExceptionInspection */

/**
 * SAMPLE - Displays the credit card transactions for a specific time range.
 *
 * Credit card accounts have no IBAN, so they are not part of getSEPAAccounts() and their transactions
 * cannot be retrieved with GetStatementOfAccount. They use the DKKKU business transaction instead,
 * which is defined by the Deutsche Kreditwirtschaft rather than by the FinTS specification, so not
 * every bank offers it.
 */

// See login.php, it returns a FinTs instance that is already logged in.
/** @var \Fhp\FinTs $fints */
$fints = require_once 'login.php';

// The credit card accounts are described in the UPD, which arrived during login, so this needs no
// request to the bank at all.
$getCreditCardAccounts = \Fhp\Action\GetCreditCardAccounts::create();
$fints->execute($getCreditCardAccounts);
$creditCardAccounts = $getCreditCardAccounts->getAccounts();

if (count($creditCardAccounts) === 0) {
    echo 'No credit card accounts found. Your bank may not support DKKKU.' . PHP_EOL;
    return;
}

echo 'Credit card accounts:' . PHP_EOL;
foreach ($creditCardAccounts as $creditCardAccount) {
    echo '  ' . $creditCardAccount->getAccountNumber()
        . ' (' . $creditCardAccount->getProductName() . ')' . PHP_EOL;
}

// Just pick the first one, for demonstration purposes.
$oneAccount = $creditCardAccounts[0];

$from = new \DateTime('-30 days');
$to = new \DateTime();
$getStatement = \Fhp\Action\GetCreditCardStatement::create($oneAccount, $from, $to);
$fints->execute($getStatement);
if ($getStatement->needsTan()) {
    handleStrongAuthentication($getStatement); // See login.php for the implementation.
}

$statement = $getStatement->getStatement();
if ($statement->getBalance() !== null) {
    echo 'Balance: ' . $statement->getBalance() . ' ' . $statement->getBalanceCurrency() . PHP_EOL;
}
echo 'Transactions:' . PHP_EOL;
echo '=======================================' . PHP_EOL;
foreach ($statement->getTransactions() as $transaction) {
    echo 'Booking date: ' . $transaction->getBookingDate()?->format('Y-m-d') . PHP_EOL;
    echo 'Amount      : ' . $transaction->getAmount() . ' ' . $transaction->getCurrency() . PHP_EOL;
    // Transactions in a foreign currency also report what the merchant originally charged.
    if ($transaction->getOriginalAmount() !== null) {
        echo 'Original    : ' . $transaction->getOriginalAmount() . ' ' . $transaction->getOriginalCurrency()
            . ' (rate ' . $transaction->getExchangeRate() . ')' . PHP_EOL;
    }
    echo 'Purpose     : ' . $transaction->getPurpose() . PHP_EOL;
    echo 'Reference   : ' . $transaction->getReference() . PHP_EOL;
    echo 'Category    : ' . ($transaction->getMerchantCategoryCode() ?? '-') . PHP_EOL;
    echo '=======================================' . PHP_EOL . PHP_EOL;
}
echo 'Found ' . count($statement->getTransactions()) . ' transactions.' . PHP_EOL;
