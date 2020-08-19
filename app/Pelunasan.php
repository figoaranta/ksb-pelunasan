<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pelunasan extends Model
{
	public $table ='pelunasans';
    protected $fillable=[
    	'pelunasanId',
    	'keterangan',
    	'bayarKepada',
    	'jumlah',
    	'tanggal',
    	'detail',
    	'diterimaOleh',
    ];
}
