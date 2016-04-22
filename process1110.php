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

	$mpdchild = $playlist->addChild("mpd");
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


function createM3U8($segnumbers)
{
	global $folder,$baseurl;
	$lowts = fopen("video_repo/$folder/low/low.m3u8", "w") or die("Unable to create m3u8 file");
	$midts = fopen("video_repo/$folder/mid/mid.m3u8", "w") or die("Unable to create m3u8 file");
	$hights = fopen("video_repo/$folder/high/high.m3u8", "w") or die("Unable to create m3u8 file");
	$rootts = fopen("video_repo/$folder/root.m3u8", "w") or die("Unable to create m3u8 file");

	fwrite($lowts, "#EXTM3U\n#EXT-X-TARGETDURATION:3\n");
	fwrite($midts, "#EXTM3U\n#EXT-X-TARGETDURATION:3\n");
	fwrite($hights, "#EXTM3U\n#EXT-X-TARGETDURATION:3\n");
	fwrite($rootts, "#EXTM3U\n");
	
	for ($i=1; $i<=$segnumbers; $i++)
	{
		fwrite($lowts, "#EXTINF:3,\n$folder-240*160---$i.ts\n");
		fwrite($midts, "#EXTINF:3,\n$folder-480*320---$i.ts\n");
		fwrite($hights, "#EXTINF:3,\n$folder-720*480---$i.ts\n");
	}

	fwrite($lowts, "#EXT-X-ENDLIST");
	fwrite($midts, "#EXT-X-ENDLIST");
	fwrite($hights, "#EXT-X-ENDLIST");
	fwrite($rootts, "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=2500000,RESOLUTION=240x160\n");
	fwrite($rootts, "low/low.m3u8\n");
	fwrite($rootts, "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=5000000,RESOLUTION=480x320\n");
	fwrite($rootts, "mid/mid.m3u8\n");
	fwrite($rootts, "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=10000000,RESOLUTION=720x480\n");
	fwrite($rootts, "high/high.m3u8\n");
	fclose($lowts);
	fclose($midts);
	fclose($hights);
}


function generatehlsPL()
{
	global $folder;
	$hlsPlaylist = "video_repo/hlsplaylist.html";
	$url = "<li><a href='http://pilatus.d1.comp.nus.edu.sg/~team15/video_repo/$folder/root.m3u8'>$folder</li>\n";
	
	if(file_exists($hlsPlaylist))
	{
		$file = fopen($hlsPlaylist, "a");
	}
	else
	{
		$file = fopen($hlsPlaylist, "w");
	}

	fwrite($file, $url);
	fclose($file);
}


$folder = $_POST["foldername"];
$vidpath = "video_repo/" . $folder . "/high/";
$mpd = new SimpleXMLElement($xmlformat);
$baseurl = $mpd->BaseURL;
$mp4files = glob($vidpath . '*.mp4');

if($mp4files !== false)
{
	$segnumbers = count( $mp4files );
	generateMPD($segnumbers);
	createM3U8($segnumbers);
}

$mpdFilePath = "video_repo/$folder/$folder.mpd";
$pathmpf = fopen($mpdFilePath, "w") or die("Unable to open file");
fwrite($pathmpf, REXML($mpd));
fclose($pathmpf);
$mpdpathPL = "$folder/$folder.mpd";

if(!file_exists("video_repo/" . $folder."/mpddone.txt"))
{
generatePL($mpdpathPL);
generatehlsPL();
$data = fopen("video_repo/" . $folder."/mpddone.txt", "w");
fclose($data);
}
?>
