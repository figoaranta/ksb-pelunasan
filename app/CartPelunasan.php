<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CartPelunasan extends Model
{
	public $table ='pelunasanCarts';
    protected $fillable=[
    	'machineId',
    	'keterangan',
    	'namaBank',
    	'cekgiro',
    	'AC',
    	'tanggalJatuhTempo',
    	'jumlah',
    ];
}
