<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class AnalysisController extends Controller
{
    public function index()
    {
        $startDate = '2024-04-10';
        $endDate = '2024-05-17';

        // $period = Order::betweenDate($startDate, $endDate)
        // ->groupBy('id')
        // ->selectRaw('id, sum(subtotal) as total, customer_name, status, created_at')
        // ->orderBy('created_at')
        // ->paginate(50);

        // $subQuery = Order::betweenDate($startDate, $endDate)
        // ->where('status', true)
        // ->groupBy('id')
        // ->selectRaw('id, sum(subtotal) as totalPerPurchase, DATE_FORMAT(created_at, "%Y%m%d") as date');

        // $data = DB::table($subQuery)
        // ->groupBy('date')
        // ->selectRaw('date, sum(totalPerPurchase) as total')
        // ->get();

        // 1. 購買ID毎にまとめる
        $subQuery = Order::betweenDate($startDate, $endDate)
        ->groupBy('id')
        ->selectRaw('id, customer_id, customer_name, SUM(subtotal) as totalPerPurchase');

        // 2. 会員毎にまとめて購入金額順にソートする
        $subQuery = DB::table($subQuery)
        ->groupBy('customer_id')
        ->selectRaw('customer_id, customer_name, sum(totalPerPurchase) as total')
        ->orderBy('total', 'desc');

        // statementで変数を設定できる
        // set @変数名 = 値 (mysqlの書き方)
        // 3. 購入順に連番を振る
        DB::statement('set @row_num = 0;');
        $subQuery = DB::table($subQuery)
        ->selectRaw('@row_num:= @row_num+1 as row_num, customer_id, customer_name, total');

        // 4. 全体の件数を数え、1/10の値や合計金額を取得
        $count = DB::table($subQuery)->count();
        $total = DB::table($subQuery)->selectRaw('sum(total) as total')->get();
        $total = $total[0]->total; // 構成比用
        $decile = ceil($count / 10); // 10分の1の件数を変数に入れる
        $bindValues = [];
        $tempValue = 0;
        for ($i = 1; $i <= 10; $i++) {
            array_push($bindValues, 1 + $tempValue);
            $tempValue += $decile;
            array_push($bindValues, 1 + $tempValue);
        }

        // 5 10分割しグループ毎に数字を振る
        DB::statement('set @row_num = 0;');
        $subQuery = DB::table($subQuery)
        ->selectRaw("
            row_num,
            customer_id,
            customer_name,
            total,
            case
                when ? <= row_num and row_num < ? then 1
                when ? <= row_num and row_num < ? then 2
                when ? <= row_num and row_num < ? then 3
                when ? <= row_num and row_num < ? then 4
                when ? <= row_num and row_num < ? then 5
                when ? <= row_num and row_num < ? then 6
                when ? <= row_num and row_num < ? then 7
                when ? <= row_num and row_num < ? then 8
                when ? <= row_num and row_num < ? then 9
                when ? <= row_num and row_num < ? then 10
            end as decile",
            $bindValues); // SelectRaw第二引数にバインドしたい数値(配列)をいれる

        // round, avg はmysqlの関数
        // 6. グループ毎の合計・平均
        $subQuery = DB::table($subQuery)
        ->groupBy('decile')
        ->selectRaw('decile, round(avg(total)) as average, sum(total) as totalPerGroup');

        // 構成比を出すために変数を使う
        // 7 構成比
        //DB::statement("set @total = ${total} ;"); // PHP8.2以降で廃止になった
        DB::statement("set @total = {$total} ;");   // PHP8.2以降の書き方
        $data = DB::table($subQuery)
        ->selectRaw('decile,
            average,
            totalPerGroup,
            round(100 * totalPerGroup / @total, 1) as
            totalRatio')
        ->get();

        return Inertia::render('Analysis');
    }
}

