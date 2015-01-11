<?php 
/**
 * DNSQuoter, see README.md for further informations
 * @author floreo
 *
 */

class DNSQuoter{
    const OVH_API_URL = 'https://api.ovh.com/1.0/';
    
    protected $_ch = null;
    protected $_conf_file = null;
    protected $_quote_file = null;
    
    public function __construct(){
        $this->_ch = curl_init();
        
        $this->_conf_file = __DIR__ . DIRECTORY_SEPARATOR . 'conf.txt';
        if(!file_exists($this->_conf_file)){
        	throw new Exception($this->_conf_file . ' not found !');
        }

        $this->_quote_file = __DIR__ . DIRECTORY_SEPARATOR . 'quotes.txt';
        if(!file_exists($this->_quote_file)){
        	throw new Exception($this->_quote_file . ' not found !');
        }
        
        // load conf
        $required = array('APPLICATION_KEY', 'APPLICATION_SECRET', 'CONSUMER_KEY', 'DOMAIN_NAME');
        $content = file($this->_conf_file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        foreach($content as $line){
        	$a = explode(':', $line);
        	$a[0] = trim($a[0]);
        	
        	if(in_array($a[0], $required)){
        		$a[1] = trim($a[1]);
        		if(empty($a[1])){
        			throw new Exception('Empty value for ' . $a[0]);
        		}
        		
        		$this->{'_' . strtolower($a[0])} = trim($a[1]);
        		// remove from $required the conf
        		unset($required[array_search($a[0], $required)]);
        	}
        }
        
        // make sure we get required conf
        if(!empty($required)){
        	throw new Exception('Not all directives where found in ' . $this->_quote_file);
        }
        
		// get a random quote
        $content = file($this->_quote_file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        if(empty($content)){
        	throw new Exception('No quotes found !');
        }
        $this->_quote = trim($content[array_rand($content)]);
    }
   
    public function __destruct(){
        curl_close($this->_ch);
    }
   
    protected function setHeaders($method, $query, $body = ''){
        if(!empty($body)){
            $body = json_encode($body);
            curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $body);
        }
        
        // not a good practice (i.e http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/)
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_ch, CURLOPT_URL, $query);
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array(
                'X-Ovh-Application:' . $this->_application_key,
                'X-Ovh-Timestamp:' . time(),
                'X-Ovh-Signature:' . '$1$' . sha1($this->_application_secret . '+' . $this->_consumer_key . '+' . $method . '+' . $query . '+' . $body . '+' . time()),
                'X-Ovh-Consumer:' . $this->_consumer_key,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($body)
            )
        );
    }
   
    public function execute(){
        $query = self::OVH_API_URL . 'domain/zone/' . $this->_domain_name . '/record?fieldType=TXT&subDomain=quotes';
        $this->setHeaders('GET', $query);
        $result = curl_exec($this->_ch);
        
        $record_id = @json_decode($result)[0];

        if(empty($record_id)){
            throw new Exception('Record not found with : ' . $query);
        }
       
        // update with new quote
        $body = array(
			'target' => $this->_quote,
        	'ttl' => 60,
        	'subDomain' => 'quotes'
        );
        $query = self::OVH_API_URL . 'domain/zone/' . $this->_domain_name . '/record/' . $record_id;
        $this->setHeaders('PUT', $query, $body);
        $result = curl_exec($this->_ch);
         
        // refresh
        $query = self::OVH_API_URL . 'domain/zone/' . $this->_domain_name . '/refresh';
        $this->setHeaders('POST', $query);
        $result = curl_exec($this->_ch);
    }
}

(new DNSQuoter())->execute();