<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getCustomers(){
    $rowNo = 1;
    $responseArray = array();
    $fsource = "./exported/customers_export_2018_08_29.csv";
    if (($fp = fopen($fsource, "r")) !== FALSE) {
        while (($rows = fgetcsv($fp, 10000, ",")) !== FALSE) {
            $num = count($rows);
            if($rowNo>1){
               // echo $rows[0]."==>".$rows[1]."<br>";
                $responseArray[$rows[1]] = $rows[0];
            }
            $rowNo++;
        }
    }
    return $responseArray;
}


$f_source = "./outlook/contacts_8_20.csv";
$f_target = "./generated/outlook-customer-users-for-orocrm".time().".csv";
function delete_col(&$array, $offset) {
    return array_walk($array, function (&$v) use ($offset) {
        array_splice($v, $offset, 1);
    });
}
/****/
$array_mapping_fields = array("First Name"=>"First Name","Last Name"=>"Last Name","Middle Name"=>"Middle Name","Suffix"=>"Name Suffix","E-mail Address"=>"Email Address","Company"=>"Customer Name"); //Accounts 1 Account name



$outlook_fields_array=array("0"=>"Name","1"=>"Title","2"=>"First Name","3"=>"Middle Name","4"=>"Last Name","5"=>"Suffix","6"=>"Company","7"=>"Account","8"=>"Suffix","9"=>"Job Title","10"=>"Business Street","11"=>"Business City","12"=>"ID","13"=>"Business Postal Code","14"=>"Business Country/Region","15"=>"Home Street","16"=>"Home City","17"=>"Home State","18"=>"Home Country/Region","19"=>"Business Fax","20"=>"Business Phone","21"=>"Business Phone 2","22"=>"Home Fax","23"=>"Home Phone","24"=>"Mobile Phone","25"=>"Primary Phone","26"=>"Categories","27"=>"E-mail Address","28"=>"E-mail Type","29"=>"E-mail Display Name","30"=>"E-mail 2 Address","31"=>"E-mail 2 Type","32"=>"E-mail 2 Display Name","33"=>"Initials","34"=>"Notes","35"=>"Priority","36"=>"Web Page");
$orocrm_fields_array=array("0"=>"ID","1"=>"Name Prefix","2"=>"First Name","3"=>"Middle Name","4"=>"Last Name","5"=>"Name Suffix","6"=>"Birthday","7"=>"Email Address","8"=>"Customer Id","9"=>"Customer Name","10"=>"Roles 1 Role","11"=>"Guest","12"=>"Enabled","13"=>"Confirmed","14"=>"Owner Id","15"=>"Website Id");
$orocrm_fields_blank_array=array("0"=>" ","1"=>" ","2"=>" ","3"=>" ","4"=>" ","5"=>" ","6"=>" ","7"=>" ","8"=>" ","9"=>" ","10"=>" ","11"=>" ","12"=>" ","13"=>" ","14"=>" ","15"=>" ");
$finalArray = array();
$array_mapping_fields_keys=array();
$checkEmtpyColumn = array();

$customerArray= getCustomers();
/******************/
 $rowNo = 1;
    if (($fp = fopen($f_source, "r")) !== FALSE) {
		 /*********Read Old file****/
        while (($rows = fgetcsv($fp, 10000, ",")) !== FALSE) {
            $num = count($rows);			
			if($rowNo==1){
				ksort($orocrm_fields_array);
				$headerFinalArray=$orocrm_fields_array;//array();
			  foreach($rows as $key=>$value)
			  {
			      //print_r($value);
				  if($value!="")
				  {
					 if(array_key_exists($value, $array_mapping_fields))
					 {
						 $orocrmMappedFieldValue=$array_mapping_fields[$value];
						
						$orocrmMappedFieldKey=array_keys($orocrm_fields_array,$orocrmMappedFieldValue,true);				
						
						$orocrmMappedFieldKey1 = $orocrmMappedFieldKey[0];				
						$array_mapping_fields_keys[$orocrmMappedFieldKey1]=$key;
					}					  
				  }
			  }
			  $finalArray[]=$headerFinalArray;
		  }
		  else{
			  $tempArray = $orocrm_fields_blank_array;
			  ksort($array_mapping_fields_keys);
			  	 foreach($array_mapping_fields_keys as $key=>$value) 
			  	 {
					// ksort($value);
			  	    // print_r($value);exit;
					 if(array_key_exists($value, $rows))
					 {
						 $arrayValues = $rows[$value];
						 if($rows[$value]=="Unspecified") // For Gender issue
						 {
							 $arrayValues = "";
						 }
						 
						 if($key ==48 || $key ==64 || $key == 80) // short country name 
						 {
							 $arrayValues = "US";
						 }			
						 
						 $tempArray[$key] = $arrayValues;
						 if(empty($checkEmtpyColumn[$key]))
						 {
							 $checkEmtpyColumn[$key]=$arrayValues;
							 if($key ==9)
							 {
							     if(isset($customerArray[$arrayValues]))
							         $checkEmtpyColumn[8] = $customerArray[$arrayValues];
							 }
							 
						 }
						 if($key ==9)
						 {
						     if(isset($customerArray[$arrayValues]))
						         $tempArray[8] = $customerArray[$arrayValues];
						 }
					 }
					 
			  	 }

				 ksort($tempArray);
				 $finalArray[]=$tempArray;
				
				 unset($tempArray);
		  }
		  $rowNo++;
        }
        fclose($fp);
        
      
       
        /*********Read Old file****/
		 $checkEmtpyColumn = array_diff($checkEmtpyColumn, array(""));
		 $checkEmtpyColumn = array_keys($checkEmtpyColumn);
 
        foreach ($finalArray as $keyParent=>$line)
		{
			foreach($line as $key=>$value)
			{
				if(!in_array($key,$checkEmtpyColumn))
				{
					unset($finalArray[$keyParent][$key]);
				}				
			}
		}

        /*********Create new file****/
        $fpwrite = fopen($f_target, "w");
 
		foreach ($finalArray as $line)
		{
			fputcsv(
				$fpwrite, // The file pointer
				$line, // The fields
				',' // The delimiter
			);      
		}
	   /*********Create new file****/
		fclose($fpwrite); 
    }exit;
/*****************/
