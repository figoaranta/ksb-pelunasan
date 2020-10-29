<?php

namespace App\Http\Controllers\Api\v1;
use App\Http\Controllers\Controller;
use App\Pelunasan;
use App\Giro;
use Illuminate\Http\Request;
use DB;
class PelunasanController extends Controller
{
	public function index()
	{
    	return Pelunasan::all();
	}
	public function show(Pelunasan $pelunasan)
	{
		$pelunasan->detail = json_decode($pelunasan->detail);
		return $pelunasan;
	}
	public function store(Request $request)
	{	
		$newPelunasanId = 0;
		$lastPelunasanId = "";
		$totalHutang = 0;
		$totalGiro = 0;
		$newArray = [];
		$bonArray =(explode(",",$request->keterangan));

		$date = (getdate());
        if(Pelunasan::all()->count() != 0){
            $number = '';
            $lastPelunasanId = Pelunasan::all()->last()->pelunasanId;
            if(strlen($date['mon'])==1){
                $date['mon'] = "0".$date['mon'];
            }
            if(strlen($date['mday'])==1){
                $date['mday'] = "0".$date['mday'];
            }
            if($lastPelunasanId[11].$lastPelunasanId[12] != $date['mday']){
                $pelunasanId = 1;
            }
            else{
                for ($i=strlen($lastPelunasanId)-1; $i > 0 ; $i--) { 
                    if($lastPelunasanId[$i] == " "){
                        break;
                    }
                $number = $number.$lastPelunasanId[$i];
                }
                $newPelunasanId = strrev($number)+1;
            }
            
			if($request->jenisVoucher == "Pelunasan Customer"){
	            $pelunasanId = 'VC-'.$date['year'].'-'.$date['mon'].'-'.$date['mday'].' '.$newPelunasanId;
			}else{
	            $pelunasanId = 'VS-'.$date['year'].'-'.$date['mon'].'-'.$date['mday'].' '.$newPelunasanId;
			}
        }
        else{
            if (strlen($date['mon'])==1){
                $date['mon'] = 0 . $date['mon'];
            }
            if (strlen($date['mday'])==1){
                $date['mday'] = 0 . $date['mday'];
            }
            if($request->jenisVoucher == "Pelunasan Customer"){
            	$pelunasanId = 'VC-'.$date['year'].'-'.$date['mon'].'-'.$date['mday'].' '.'1';
            } else{
            	$pelunasanId = 'VS-'.$date['year'].'-'.$date['mon'].'-'.$date['mday'].' '.'1';
            }
            
        }

		if(count($bonArray)>1){
			$penjual = DB::connection('mysql2')->table('suppliers')->where('nomorBon',$bonArray[0])->first()->penjual;
		}else{
			$penjual = DB::connection('mysql2')->table('suppliers')->where('nomorBon',$request->keterangan)->first()->penjual;
		}
		if ($request->jenisVoucher == "Pelunasan Customer"){
			$seluruhHutang = DB::connection('mysql3')->table('piutangs')->where('penjual',$penjual);
		}else{
			$seluruhHutang = DB::connection('mysql3')->table('hutangs')->where('penjual',$penjual);
		}
		
		$giros = Giro::where('machineId',UniqueMachineID2())->get();

		$giroId = '';
		$namaBank = '';
		$account = '';
		$tanggalJatuhTempo = '';
		$jumlah = '';
		foreach ($giros as $giro) {
			$giroId = $giroId . $giro->giroId . ",";
			$namaBank = $namaBank . $giro->namaBank . ",";
			$account = $account . $giro->account . ",";
			$tanggalJatuhTempo = $tanggalJatuhTempo . $giro->tanggalJatuhTempo.",";
			$jumlah = $jumlah . $giro->jumlah . ",";
		}

		$object = (object) [
				'giroId' =>substr($giroId, 0, -1),
				'namaBank'=>substr($namaBank, 0, -1),
				'account'=>substr($account, 0, -1),
				'tanggalJatuhTempo'=>substr($tanggalJatuhTempo, 0, -1),
				'jumlah'=>substr($jumlah, 0, -1)
		];
		// return $object;
		// 	array_push($newArray,$object);

		foreach ($giros as $giro) {
			$totalGiro = $totalGiro+$giro->jumlah;
		}

		if (count($giros)==0) {
		 	return response()->json(["Giro is currently empty"]);
		}

		$encode = json_encode($object);

		$request->validate([
			'keterangan'=>'required',
			'bayarKepada'=>'required',
			'jumlah'=>'required',
			'tanggal'=>'required',
			// 'keterangan'=>'required',
			'diterimaOleh'=>'required',
		]);

		if ($request->jumlah != $totalGiro) {
			return response()->json(["Jumlah dan total jumlah giro berbeda."]);
		}
		

		foreach ($bonArray as $bonId) {
			$hutang = DB::connection('mysql2')->table('suppliers')->where('nomorBon',$bonId)->first()->hargaTotal;
			$totalHutang =  $totalHutang+$hutang;
		}
		if($request->jumlah < $totalHutang){
			return response()->json(["Total hutang is greater than jumlah yang mau dibayar."]);
		}
		
		$pelunasan = Pelunasan::create([
			'pelunasanId'=>$pelunasanId,
			'keterangan'=>$request->keterangan,
			'bayarKepada'=>$request->bayarKepada,
			'jumlah'=>$request->jumlah,
			'tanggal'=>$request->tanggal,
			'detail'=>$encode,
			'diterimaOleh'=>$request->diterimaOleh,
		]);

		if ($request->jenisVoucher == "Pelunasan Supplier"){
			foreach ($bonArray as $bonId) {
				$hutang = DB::connection('mysql2')->table('suppliers')->where('nomorBon',$bonId);
				$hutang->update(['lunas'=>true]);
			}
		}else{
			foreach ($bonArray as $bonId) {
				$hutang = DB::connection('mysql2')->table('clients')->where('nomorBon',$bonId);
				$hutang->update(['lunas'=>true]);
			}
		}
		

		if (isset($seluruhHutang->get()[0])) {
			if($seluruhHutang->first()->total != 0){
				$seluruhHutang->update([
					'total'=>$seluruhHutang->first()->total - $totalHutang
				]);
			}
		}

		Giro::where('machineId',UniqueMachineID2())->delete();

		return $pelunasan;
	}
}

function UniqueMachineID2($salt = "") {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $temp = sys_get_temp_dir().DIRECTORY_SEPARATOR."diskpartscript.txt";
        if(!file_exists($temp) && !is_file($temp)) file_put_contents($temp, "select disk 0\ndetail disk");
        $output = shell_exec("diskpart /s ".$temp);
        $lines = explode("\n",$output);
        $result = array_filter($lines,function($line) {
            return stripos($line,"ID:")!==false;
        });
        if(count($result)>0) {
            $result = array_shift(array_values($result));
            $result = explode(":",$result);
            $result = trim(end($result));       
        } else $result = $output;       
    } else {
        $result = shell_exec("blkid -o value -s UUID");  
        if(stripos($result,"blkid")!==false) {
            $result = $_SERVER['HTTP_HOST'];
        }
    }   
    return md5($salt.md5($result));
}