<?php

namespace ApironeApi;
use ApironeApi\Apirone;

require_once(__DIR__ . '/Apirone.php');
class Payment
{

    use Utils;

    public static function invoice($invoice, $currency, $statusLink, $merchant='', $back2store = '/')
    {
        $details = $invoice->details;
        $totalAmount = Payment::exp2dec( Payment::min2cur($details->amount, $currency->{'units-factor'}) );

        $remains = $details->amount;

        foreach ($details->history as $item) {
            if (property_exists($item, 'amount')) {
                $remains = $remains - $item->amount;
            }
        }

        $remains = Payment::exp2dec( Payment::min2cur($remains, $currency->{'units-factor'}) );

        $status = $invoice->status;
        $statusCode = Payment::invoiceStatus($invoice);
        $expired = strtotime($details->expire);

        if ($status == 'paid' || $status == 'overpaid' || $status == 'completed' || $status == 'expired') {
            $countdown = -1;
        }
        else {
            $countdown = $expired - time();    
        }
        $statusMessage = '';
        $statusMessageDesc = '';
        ob_start();
        ?>
        <div class="mccp-wrapper">
            <div id="mccp-payment" class="mccp-payment <?php echo $status; ?>">
                <div class="invoice__info">
                    <div class="qr__wrapper">
                    <?php if ($status == 'created' || $status == 'partpaid') : ?>
                        <img src="<?php echo Payment::renderQr($currency, $details->address, $remains); ?>">
                        <?php $statusMessageDesc = ($countdown >= 0) ? 'Waiting for payments...' : 'Updating status...'; ?>
                    <?php else: ?>
                        <?php

                            switch($status) {
                                case 'paid':
                                case 'overpaid':
                                    $statusMessage = 'Payment accepted';
                                    $statusMessageDesc = 'Waiting for confirmations...';
                                    break;
                                case 'completed':
                                    $statusMessage = 'Payment completed';
                                    break;
                                case 'expired':
                                    $statusMessage = 'Payment expired';
                                    break;
                            }
                        ?>
                        <img src="<?php echo Payment::renderQr($currency, $details->address, $totalAmount); ?>" class="blur">
                        <div class="status-icon-wrapper">
                            <div class="status-icon"></div>
                        </div> 
                    <?php endif; ?>
                        <a id="statusUrl" href="<?php echo $statusLink; ?>" style="display: none"></a>
                    </div>
                    <div class="info">
                        <div class="info__title">
                            <img src="<?php echo $currency->icon; ?>" class="currency-icon<?php echo $currency->testnet ? ' test-currency' : ''; ?>">
                            <?php echo $currency->name; ?>
                        </div>
                        <?php if ($merchant) : ?>
                        <p class="merchant"> Invoice by: <span><?php echo $merchant; ?></span> </p>
                        <?php endif; ?>
                        <p class="info__date">Date: <span class="date-gmt"><?php echo $details->created . 'Z'; ?></span></p>
                        <p class="info__amount"><?php echo $totalAmount; ?> (<?php echo strtoupper($currency->abbr); ?>)</p>

                        <?php if(empty($statusMessage)) : ?>
                        
                        <div class="mccp-countdown">
                            <input type="hidden" id="countdown" value="<?php echo $countdown; ?>">
                            <div class="stopwatch">
                                <span class="stopwatch__icon"></span>
                                <span id="stopwatch" class="stopwatch__numbers"></span>
                            </div>
                        </div>
                        
                        <?php else: ?>

                        <div class="mccp-status">
                            <?php echo $statusMessage; ?>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($statusMessageDesc)) : ?>
                        <div class="mccp-status-desc">
                            <?php echo $statusMessageDesc; ?>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
                <div class="mccp-amount">

                <?php if($status == 'created' || $status == 'partpaid') : ?>

                    <div class="info-field mccp-address">
                        <span>Payment address</span>
                        <div>
                            <span class="copy2clipboard"><?php echo $details->address; ?></span>
                            <a class="address-link" href="<?php echo Payment::getAddressLink($currency, $details->address); ?>" target="_blank"></a>
                        </div>
                    </div>
                    <div class="info-field mccp-remains">
                        <span>Remains (<?php echo strtoupper($details->currency); ?>)</span>
                        <div>
                            <span class="copy2clipboard"><?php echo $remains; ?></span>
                        </div>
                    </div>

                <?php endif; ?>

                </div>

                <div class="payment-details">
                    <div class="loader-wrapper">
                        <div class="mccp-loader"></div>
                    </div>
                    <div class="info-list">
                        <span>Payment history</span>
                        <div>
                        <ul>
                            <?php foreach ($details->history as $item) : ?>
                                <li class="item">
                                <div>
                                    <span class="date-gmt"><?php echo $item->date . 'Z'; ?></span> 
                                    <?php if (property_exists($item, 'txid')) : ?>
                                    <a class="address-link" href="<?php echo Payment::getTransactionLink($currency, $item->txid); ?>" target="_blank">
                                        (<?php echo Payment::exp2dec( Payment::min2cur($item->amount, $currency->{'units-factor'}) ); ?>)
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div><strong><?php echo $item->status; ?></strong></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        </div>
                    </div>
                    <input type="hidden" id = "current" value="<?php echo $statusCode; ?>">
                </div>
                <a id="back2store" href="<?php echo $back2store; ?>">Back to store</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function getCoins($account, $total, $from = 'usd', $showTestnet = false)
    {
        return self::preparePayment($account, $total, $from, $showTestnet);
    }

    public static function currencySelector($account, $total, $from = 'usd', $showTestnet = false)
    {
        $currencies = self::preparePayment($account, $total, $from, $showTestnet);
        $count = count($currencies);

        // Show empty
        if ($currencies == false || $count == 0) {
            return '<strong>Sorry, payment method temporarily unavailable</strong><input id="mccp-currency" type="hidden" name="currency" required>';
        }

        // Show single
        if ($count == 1) {
            $item = $currencies[0];
            ob_start();

            $title = $item->name . ': ' . (($item->amount) ? $item->amount : "Sorry, can't convert $total $from to " . strtoupper($item->abbr));
            echo '<div>' . $title . '</div>';
            if ($item->amount) {
                echo '<input id="mccp-currency" type="hidden" name="currency" value="' . $item->abbr . '" required>';
            }

            return ob_get_clean();
        }

        // Show multiple
        ob_start();
        echo '<select id="mccp-currency" name="currency" required>';
        foreach ($currencies as $item) {
            $disabled = (!$item->payable || !$item->amount) ? ' disabled' : '';
            $title = $item->name . ': ' . (($item->amount) ? $item->amount : "Sorry, can't convert $total $from to " . strtoupper($item->abbr));
            echo '<option value="' . $item->abbr . '"' . $disabled . '>' . $title . '</option>';
        }
        echo '</select>';

        return ob_get_clean();
    }


    protected static function preparePayment($account, $total, $from = 'usd', $showTestnet = false)
    {
        $currencies = Apirone::accountCurrencyList($account, true);
        if ($currencies == false) {
            return false;
        }

        $currenciesList = array();
        foreach ($currencies as $item) {
            if ($showTestnet == false && $item->testnet == 1) {
                continue;
            }
            $item->amount = self::exp2dec(self::fiat2crypto($total, $from, $item->abbr));
            $item->payable = ( $item->amount && $item->amount >= self::min2cur($item->{'dust-rate'}, $item->{'units-factor'}) ) ? 1 : 0;
            $currenciesList[] = $item;
        }

        return $currenciesList;
    }

    public static function makeInvoiceData($currency, $amount, $lifetime, $callback, $order_amount, $order_currency)
    {

        $invoice = new \stdClass();

        $invoice->currency = $currency;
        $invoice->amount = $amount;
        $invoice->lifetime = $lifetime;
        $invoice->callback_url = $callback;

        $invoice->{'user-data'} = new \stdClass();
        $invoice->{'user-data'}->price = new \stdClass();
        $invoice->{'user-data'}->price->amount = $order_amount;
        $invoice->{'user-data'}->price->currency = $order_currency;

        return $invoice;
    }

    public static function makeInvoiceSecret($baseSecret, $additional)
    {
        return md5($baseSecret . $additional);
    }

    public static function checkInvoiceSecret($invoiceSecret, $baseSecret, $additional)
    {
        return ($invoiceSecret == self::makeInvoiceSecret($baseSecret, $additional)) ? true : false;
    }

    public static function invoiceStatus($invoice)
    {
        if ($invoice) {
            if ($invoice->status == 'expired' || $invoice->status == 'completed')
                return 0;
            return count($invoice->details->history);
        }
        return '';
    }
}
