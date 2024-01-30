<?php

class ModelExtensionPaymentApironeMccp extends Model
{
    public function install_invoices_table($query = '')
    {
        if(empty($query)) {
            return;
        }
        $this->db->query($query);
    }

    public function delete_invoices_table($query = '')
    {
        if(empty($query)) {
            return;
        }
        $this->db->query($query);
    }
}
