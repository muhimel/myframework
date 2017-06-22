<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Models;
use Lib\ORM\Model;
/**
 * Description of Order
 *
 * @author Himel
 */
class Order extends Model{
    private $table = "orders";
    
    
    
    public function Products()
    {
        return $this->hasMany("\Models\Product", "id", "order_id");
    }
}
