<?php

namespace ApironeApi;

class Db
{

    const TABLE_INVOICE = 'apirone_mccp';

    /**
     * Return create invoice table SQL query
     * 
     * @param string $db_prefix 
     * @param string $charset 
     * @param string $collate 
     * @return string 
     */
    public static function createInvoicesTableQuery ($db_prefix = '', $charset = 'utf8', $collate = 'utf8_general_ci')
    {
        $invoice_table = $db_prefix . self::TABLE_INVOICE;

        $charset_collate = '';
        if ( ! empty( $charset ) ) {
            $charset_collate = "DEFAULT CHARACTER SET $charset";
        }
        if ( ! empty( $collate ) ) {
                $charset_collate .= " COLLATE $collate";
        }

        $query = "CREATE TABLE IF NOT EXISTS `$invoice_table` (
            `id` int NOT NULL AUTO_INCREMENT,
            `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `order_id` int NOT NULL DEFAULT '0',
            `account` varchar(64) NOT NULL,
            `invoice` varchar(64) NOT NULL,
            `status` varchar(10) NOT NULL,
            `details` text NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM $charset_collate;";

        return $query;
    }

    /**
     * Return drop invoice table SQL query
     * 
     * @param mixed $db_prefix 
     * @return string 
     */
    public static function deleteInvoicesTableQuery ($db_prefix)
    {
        $invoice_table = $db_prefix . self::TABLE_INVOICE;

        $query = "DROP TABLE IF EXISTS `$invoice_table`;";

        return $query;
    }

    /**
     * Return order invoice by order_id SQL query
     * 
     * @param mixed $order_id 
     * @param string $db_prefix 
     * @return string 
     */
    public static function getOrderInvoiceQuery($order_id, $db_prefix = '')
    {
        $invoice_table = $db_prefix . self::TABLE_INVOICE;

        $query = "SELECT * FROM `$invoice_table` WHERE `order_id` = $order_id";

        return $query;
    }

    /**
     * Return invoice by invoice id SQL query
     * 
     * @param mixed $invoice 
     * @param string $db_prefix 
     * @return string 
     */
    public static function getInvoiceQuery($invoice, $db_prefix = '')
    {
        $invoice_table = $db_prefix . self::TABLE_INVOICE;

        $query = "SELECT * FROM `$invoice_table` WHERE `invoice` = \"$invoice\"";

        return $query;
    }

    /**
     * Retur insert invoice SQL query
     * 
     * @param mixed $invoice 
     * @param string $db_prefix 
     * @return string 
     */
    public static function insertInvoiceQuery($invoice, $db_prefix = '')
    {
        $invoice_table = $db_prefix . self::TABLE_INVOICE;

        $query = "INSERT INTO `" . $invoice_table . "` " . 
            "SET " . 
            "`order_id` = " . (int) $invoice['order_id'] . "," .
            "`account` = '" . $invoice['account'] . "', " .
            "`invoice` = '" . $invoice['invoice'] . "', " .
            "`status` = '" . $invoice['status'] . "', " .
            "`details` = '" . json_encode($invoice['details']) . "'";

        return $query;
    }

    /**
     * Return update invoice SQL query
     * 
     * @param mixed $invoice 
     * @param string $db_prefix 
     * @return string 
     */
    public static function updateInvoiceQuery($invoice, $db_prefix = '')
    {
        $invoice_table = $db_prefix . self::TABLE_INVOICE;

        $query = "UPDATE `" . $invoice_table . "` " . 
            "SET " .
            "`status` = '" . $invoice['status'] . "', " .
            "`details` = '" . json_encode($invoice['details']) . "' " . 
            "WHERE `order_id` = " . $invoice['order_id'];

        return $query;
    }
}
