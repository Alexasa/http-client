# http-client
PHP 7 Application Design Assessment

## Using:
```
require('HttpClient.class.php');

//where to send
$endpoint = '{API URL}';
//what to send
$data = array( {SOME DATA} );
//create object
$client = new HttpClient($endpoint,'POST');
//send data
$ret = $client->send($data);
//show response
echo $ret;
```
