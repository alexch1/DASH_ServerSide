<?php

$xmlformat = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<MPD xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xmlns="urn:mpeg:mpegB:schema:DASH:MPD:DIS2011"
     xsi:schemaLocation="urn:mpeg:mpegB:schema:DASH:MPD:DIS2011"
     profiles="urn:mpeg:mpegB:profile:dash:full:2011"
     minBufferTime="PT2.0S">
     <BaseURL>http://pilatus.d1.comp.nus.edu.sg/~team15/video_repo/</BaseURL>
     <Period start="PT0S">
        <Group mimeType="video/mp4">
            <Representation width="720" height="480" id="high" bandwidth="3000000">
                <SegmentInfo duration="PT03.00S">
                  	<UrlTemplate indexURL="$Index$.mp4">
                  	</UrlTemplate>
                </SegmentInfo>
            </Representation>
            <Representation width="480" height="320" id="medium" bandwidth="768000">
                <SegmentInfo duration="PT03.00S">
                 	<UrlTemplate indexURL="$Time$.mp4">
                  	</UrlTemplate>
                </SegmentInfo>
            </Representation>
            <Representation width="240" height="160" id="low" bandwidth="200000">
                <SegmentInfo duration="PT03.00S">
                	<UrlTemplate indexURL="$Bandwidth$.mp4">
                  	</UrlTemplate>
                </SegmentInfo>
            </Representation>
        </Group>
    </Period>
</MPD>
XML;

$playlistxml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<playlist>
</playlist>
XML;


function generateMPD($segnumbers)
{
	global $mpd, $folder;
	$high = $mpd->Period->Group->Representation[0]->SegmentInfo->UrlTemplate; 
	$mid = $mpd->Period->Group->Representation[1]->SegmentInfo->UrlTemplate; 
	$low = $mpd->Period->Group->Representation[2]->SegmentInfo->UrlTemplate; 

	$high->addAttribute("sourceUrl" , $folder . "/high/" . $folder . "-720*480---");
    $high->addAttribute("endIndex" , $segnumbers);
	$mid->addAttribute("sourceUrl" , $folder . "/mid/" . $folder . "-480*320---");
	$mid->addAttribute("endIndex" , $segnumbers);
	$low->addAttribute("sourceUrl" , $folder . "/low/" . $folder . "-240*160---");
    $low->addAttribute("endIndex" , $segnumbers);
}


function generatePL($mpdFilePath)
{
	global $baseurl, $playlistxml;
	$attr = $baseurl . $mpdFilePath;
	$playlistpath = "video_repo/playlist.xml";
	
	if(file_exists($playlistpath))
	{
		$playlist = simplexml_load_file($playlistpath);
	}
	else
	{
		try {$playlist = new SimpleXMLElement($playlistxml);}
		catch(Exception $e) {echo $e->getMessage();}
	}

	$mpdchild = $playlist->addChild("mpdlive");
	$mpdchild->addAttribute("path", $attr);
	$fp = fopen($playlistpath, "w") or die("can't open");
	fwrite($fp, REXML($playlist));
	fclose($fp);
}


function REXML($XML)
{
	$dom = new DOMDocument('1.0');
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	$dom->loadXML($XML->asXML());
	echo $dom->saveXML();
	return $dom->saveXML();
}


if (file_exists("logs/logs.txt"))
	$log = fopen("logs/logs.txt", "a");
else
	$log = fopen("logs/logs.txt", "w");


function debug($INFO)
{
	global $log;
	$INFO = "debuginfo--------------- " .$INFO . "\n";
	echo $INFO;
	fwrite($log, $INFO);
}


function debugdata($INFO)
{
	global $data;
	$INFO = $INFO." ";
	echo $INFO;
	fwrite($data, $INFO);
}


function transcode()
{
	global $vidname, $vidpath, $segID;
	$fps = "29.97";
	$asps = "44100";
	$audiobitrate = "64";
	$lowdir = "video_repo/$vidname/low";
	$lowvidname = "$lowdir/$vidname-240*160---$segID.mp4";
	$middir = "video_repo/$vidname/mid";
	$midvidname = "$middir/$vidname-480*320---$segID.mp4";
	$highdir = "video_repo/$vidname/high";
	$highvidname = "$highdir/$vidname-720*480---$segID.mp4";
	$lowrate = "200";
	$midrate = "768";
    $highrate = "3072";
	$lowres = "240x160";
	$midres = "480x320";
	$highres = "720x480";
	$lowts = "$lowdir/$vidname-240*160---$segID.ts";
	$midts = "$middir/$vidname-480*320---$segID.ts";
	$hights = "$highdir/$vidname-720*480---$segID.ts";

	if(!file_exists($lowdir))
		mkdir($lowdir, 0777, true);
	
	if(!file_exists($middir))
		mkdir($middir, 0777, true);
		
	if(!file_exists($highdir))
	    mkdir($highdir, 0777, true);

	$cmd1 = "/usr/local/bin/convert.sh $vidpath $lowrate $fps $lowres $asps $audiobitrate $lowvidname";
	$cmd2 = "/usr/local/bin/convert.sh $vidpath $midrate $fps $midres $asps $audiobitrate $midvidname";
	$cmd3 = "/usr/local/bin/mp42ts $lowvidname $lowts";
	$cmd4 = "/usr/local/bin/mp42ts $midvidname $midts";
	$cmd5 = "/usr/local/bin/convert.sh $vidpath $highrate $fps $highres $asps $audiobitrate $highvidname";
	$cmd6 = "/usr/local/bin/mp42ts $highvidname $hights";
	
	system($cmd1);
	system($cmd2);
	system($cmd5);
    system($cmd3);
	system($cmd4);
	system($cmd6);
}


$segID="";
$vidname="";
$viddir="";
$vidpath="";

if(!strcmp($_POST["Type"],"Info"))
{
	$vidname=$_POST["videoname"];
	$filename = "video_repo/$vidname/data.txt";
	$handle = fopen($filename, "r");
	$contents = fread($handle, filesize($filename));
	debug("(list) ".$contents);
	fclose($handle);
    fclose($log);
	exit;
}

else if (!strcmp($_POST["Type"],"live"))
{
	$segID= $_POST["ID"];
	$vidname=$_POST["videoname"];
	$viddir = "video_repo/" . $vidname . "/actual";
	$vidpath = "$viddir/$vidname-actual---$segID.mp4"; 
	$folder = $_POST["videoname"];
	
	if(!file_exists($viddir))
		mkdir($viddir, 0777, true);  

    move_uploaded_file($_FILES["uploaded"]["tmp_name"], $vidpath);  
    transcode();
        
    if($segID==1)
    {
     	$mpd = new SimpleXMLElement($xmlformat);
		$baseurl = $mpd->BaseURL;
		$mp4files = glob($vidpath . '*.mp4');

		if($mp4files !== false)
		{
			$segnumbers = -1*$segID;
			generateMPD($segnumbers);
		}

		$mpdFilePath = "video_repo/$folder/$folder.mpd";
		$pathmpd = fopen($mpdFilePath, "w") or die("Unable to open file");
		fwrite($pathmpd, REXML($mpd));
		fclose($pathmpd);
		$mpdpathPL = "$folder/$folder.mpd";
		generatePL($mpdpathPL);
    }
    else
    {
    	$mpd = new SimpleXMLElement($xmlformat);
		$baseurl = $mpd->BaseURL;
		$mp4files = glob($vidpath . '*.mp4');
		if($mp4files !== false)
		{
				
			$segnumbers = -1*$segID;
			generateMPD($segnumbers);
		}
			
		$mpdFilePath = "video_repo/$folder/$folder.mpd";
		$pathmpd = fopen($mpdFilePath, "w") or die("Unable to open file");
		fwrite($pathmpd, REXML($mpd));
		fclose($pathmpd);
	}
}


else
{
	if (($_FILES["uploaded"]["size"] < 20000000))
	{
		if ($_FILES["uploaded"]["error"] > 0)
		{
	   		$error = "Return Code: " . $_FILES["uploaded"]["error"];
	   		debug($error);
       	}
   		else
      	{
	   		$segID= $_POST["ID"];
	   		$vidname=$_POST["videoname"];
	   		$viddir = "video_repo/" . $vidname . "/actual";
	   		$vidpath = "$viddir/$vidname-actual---$segID.mp4"; 
        
       		if(!file_exists("video_repo/$vidname/data.txt"))
         	{
    			$data = fopen("video_repo/$vidname/data.txt", "w");
        		fwrite($data,"1 ");
        		fclose($data);
        		$data = fopen("video_repo/$vidname/data.txt", "a");   
        		debugdata($segID);
    			fclose($data);
    	  	}
   			else
        	{    
    			$data = fopen("video_repo/$vidname/data.txt", "a");   
    			debugdata($segID);
    			fclose($data);
    		}
    
        	if(!file_exists($viddir))
				mkdir($viddir, 0777, true);

    		move_uploaded_file($_FILES["uploaded"]["tmp_name"], $vidpath);  
        	transcode();
        
       }
    }
 
	else
	{
   		echo "Oppos";
   	}
	
	fclose($log);
 	exit;
}
?>
