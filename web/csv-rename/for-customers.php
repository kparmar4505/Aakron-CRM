<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$f_source = "./outlook/contacts_8_20.csv";
$f_target = "./generated/outlook-customer-for-orocrm".time().".csv";
function delete_col(&$array, $offset) {
    return array_walk($array, function (&$v) use ($offset) {
        array_splice($v, $offset, 1);
    });
}
/****/
$array_mapping_fields = array("Company"=>"Name","Account"=>"account_number"); //Accounts 1 Account name



$outlook_fields_array=array("0"=>"Name","1"=>"Title","2"=>"First Name","3"=>"Middle Name","4"=>"Last Name","5"=>"Suffix","6"=>"Company","7"=>"Account","8"=>"Suffix","9"=>"Job Title","10"=>"Business Street","11"=>"Business City","12"=>"ID","13"=>"Business Postal Code","14"=>"Business Country/Region","15"=>"Home Street","16"=>"Home City","17"=>"Home State","18"=>"Home Country/Region","19"=>"Business Fax","20"=>"Business Phone","21"=>"Business Phone 2","22"=>"Home Fax","23"=>"Home Phone","24"=>"Mobile Phone","25"=>"Primary Phone","26"=>"Categories","27"=>"E-mail Address","28"=>"E-mail Type","29"=>"E-mail Display Name","30"=>"E-mail 2 Address","31"=>"E-mail 2 Type","32"=>"E-mail 2 Display Name","33"=>"Initials","34"=>"Notes","35"=>"Priority","36"=>"Web Page");
$orocrm_fields_array=array("0"=>"Id","1"=>"Name","2"=>"Parent Id","3"=>"Group Name","4"=>"Owner Id","5"=>"Tax code","6"=>"Account Id","7"=>"account_number","8"=>"Payment term Label","9"=>"Internal rating Id");
$orocrm_fields_blank_array=array("0"=>" ","1"=>" ","2"=>" ","3"=>"All Customers","4"=>"1","5"=>"Tax_code_1","6"=>" ","7"=>" ","8"=>"net 90","9"=>"1_of_5");
$finalArray = array();
$array_mapping_fields_keys=array();
$checkEmtpyColumn = array();
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
