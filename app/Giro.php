<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Giro extends Model
{
	// protected $primaryKey = 'giroId';
	public $table ='giros';
    protected $fillable=[
    	'giroId',
    	'machineId',
    	'namaBank',
    	'account',
    	'tanggalJatuhTempo',
    	'jumlah',
    ];
}
