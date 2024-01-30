<?php

namespace Opencart\Admin\Model\Extension\Apirone\Payment;

class ApironeMccp extends \Opencart\System\Engine\Model
{
    public function install_invoices_table($query = ''): void
    {
        if(empty($query)) {
            return;
        }
        $this->db->query($query);
    }

    public function delete_invoices_table($query = ''): void
    {
        if(empty($query)) {
            return;
        }
        $this->db->query($query);
    }
}
