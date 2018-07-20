<?php

$json = '{"appid":"wx4bd459545a672aaa","attach":"test","bank_type":"CFT","cash_fee":"1","fee_type":"CNY","is_subscribe":"N","mch_id":"1490631652","nonce_str":"fs2e5189ynuf53avjkhosol4o7a587ko","openid":"o6M-84ijtWV5BfpNCyAdDDtxiySo","out_trade_no":"sdkphp20180720113712","result_code":"SUCCESS","return_code":"SUCCESS","sign":"C4E9BBBBDDA4D7E3492B7076DBB48F83D60E006D75CF9D5B1F7B6498FF2BDE76","time_end":"20180720113725","total_fee":"1","trade_type":"JSAPI","transaction_id":"4200000113201807209504340689"}';

$json = json_decode($json,TRUE);

print_r($json);

?>