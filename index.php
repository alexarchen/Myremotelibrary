<?php
  
setlocale(LC_ALL, "Ru.1251");

$MIME=array('.txt'=>'text/plain','.jpg'=>'image/jpeg','.png'=>'image/png','.mp4'=>'video/mp4','.mkv'=>'video/x-matroska','.avi'=>'video/avi','.ts'=>'video/mp2t');
$BASE_DIR = '../../../Multimedia/Video';
$download_rate = 3000; // 20 Mbit
                                 
if (isset($_GET['browse']))
{ 
 $focus="focus";
 $Folder="";
 if (isset($_GET['Folder'])) 
  {
   $Folder=urldecode($_GET['Folder']);
   
  if ($Folder!='')
   echo "<li id='back_ref' data='".urlencode(substr($Folder,0,strrpos ($Folder,"/")))."'>..</li>\n";
  }
 $Files =  scandir($BASE_DIR.$Folder);


$directories = array();
$files_list  = array();
foreach($Files as $file){
   if(($file != '.') && ($file != '..'))
   {
      if(is_dir($BASE_DIR.$Folder."/".$file)){
         $directories[]  = $file;

      }else{
         $files_list[]    = $file;

      }
   }
}

 $Files = array_merge($directories, $files_list);

 //if ($handle = opendir($BASE_DIR.$Folder)) 
 // {

   // while (false !== ($entry = readdir($handle))) 
    foreach ($Files as $entry)
    {
        if ($entry != "." && $entry != ".." && ($entry{0}!='.')) {
         if (is_file($BASE_DIR.$Folder."/".$entry))
          {
           $ext = strtolower(strrchr ($entry,"."));                                                                  
           if (($ext=='.mp4') || ($ext=='.y') || ($ext=='.mkv') || ($ext=='.ts') /*|| ($ext=='.avi')*/) // avi isn't supported by html5
           {
            $id='';
              if ($ext=='.y'){

                // gettings image

               $id =  file_get_contents($BASE_DIR.$Folder."/".$entry);
               $id = str_replace('http://www.youtube.com/watch?v=','',$id);
               echo "<li class='navigation-item nav-item $focus' onclick='item_click(this)' play='true' data-id='$id' data='".urlencode("$Folder/$entry")."'><div class='thumb'><img width='208' src='?play&path=".urlencode("$Folder/$entry.jpg")."'></div><div class='title'><br>".substr($entry,0,strlen($entry)-strlen($ext))."</div></li>\n";
             }
            else
              echo "<li class='navigation-item nav-item $focus' play='true' data='".urlencode("$Folder/$entry")."'><a href='?play&path=".urlencode("$Folder/$entry")."'><div class='thumb'><img width='208' src='?play&path=".urlencode("$Folder/$entry.jpg")."'></div><div class='title'><br>".substr($entry,0,strlen($entry)-strlen($ext))."</div></a></li>\n";


            if (!file_exists($BASE_DIR.$Folder."/".$entry.".jpg"))
             {                                    
              if ($ext=='.y'){

                // gettings image

                   $img = file_get_contents("http://img.youtube.com/vi/".$id."/0.jpg");
                   if (strlen($img))
                    file_put_contents($BASE_DIR.$Folder."/".$entry.".jpg",$img);
                }
             }
           

            $focus="";
           }

          }
          else
           {
            if (file_exists($BASE_DIR.$Folder."/".$entry."/cover.jpg"))
             echo "<li class='navigation-item nav-item $focus' onclick='item_click(this)' data='".urlencode("$Folder/$entry")."'><img width='208' src='?play&path=".urlencode("$Folder/$entry/cover.jpg")."'></li>\n";
            else
            if (file_exists($BASE_DIR.$Folder."/".$entry."/cover.png"))
             echo "<li class='navigation-item nav-item $focus' onclick='item_click(this)' data='".urlencode("$Folder/$entry")."'><img width='208' src='?play&path=".urlencode("$Folder/$entry/cover.png")."'></li>\n";
            else
            echo "<li class='navigation-item nav-item $focus' onclick='item_click(this)' data='".urlencode("$Folder/$entry")."'>$entry</li>\n";
        $focus="";
          }
 
     }         
    }
  //  closedir($handle);
  //}
                

 
 exit;
}
else
if (isset($_GET['play']))
{


 $path = urldecode($_GET['path']);
 $ext = strrchr($path,'.');
 if (($ext) && (isset($MIME[$ext])))
  {
   if (file_exists($BASE_DIR.$path))
   {

    $len = filesize($BASE_DIR.$path);
    //echo $len;

    if (isset($_SERVER['HTTP_RANGE']))
    {
        list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

        if ($size_unit == 'bytes')
        {
            //multiple ranges could be specified at the same time, but for simplicity only serve the first range
            //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
            list($range, $extra_ranges) = explode(',', $range_orig, 2);
        }
        else
        {
            $range = '';
        }
    }
    else
    {
        $range = '';
    }

    //figure out download piece from range (if set)
    list($seek_start, $seek_end) = explode('-', $range, 2);
    list($_seek_start, $_seek_end) = explode('-', $range, 2);

    //set start and end based on range (if set), else set defaults
    //also check for invalid ranges.
    $seek_end = (empty($seek_end)) ? ($len - 1) : min(abs(intval($seek_end)),($len - 1));
    $seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
  

    header ("Content-Length: ".($seek_end-$seek_start+1));


    if ($seek_start > 0 || $seek_end < ($len - 1))
        {
            header('HTTP/1.1 206 Partial Content');
        }
    header('Content-Type: '.$MIME[$ext]);
    header ("Content-Disposition: filename='video_file$ext'");

     header('TransferMode.DLNA.ORG: Streaming');
     header('Accept-Ranges: bytes');
     header('Connection: keep-alive');
     header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$len);
     header('File-Size: '.$len);

    flush();
    ob_flush();

    $file = fopen($BASE_DIR.$path, "rb");

    fseek($file,$seek_start);

$f = fopen("log.txt","a+");
if ($f)
{

 fwrite ($f,$_SERVER['REQUEST_METHOD']." $path $seek_start $seek_end $len\n");
  foreach (getallheaders() as $name=>$val)
   fwrite($f,"- Request $name=$val\n"); 

fclose($f);
}



 
    while(!feof($file))
    {
        // send the current file part to the browser
        print fread($file, min($seek_end-$seek_start+1,2001024));
        // flush the content to the browser
        if (ftell($file)>=$seek_end) break;
        flush();                
        ob_flush();
       
        // sleep one second
        usleep(200000);


       $f = fopen("log.txt","a+");
       if ($f)
         {
         fwrite ($f,time()." Sent 1M block, status ".connection_status()."\n");
         fclose($f);
         }
        if (connection_aborted()) break;

    }
    fclose($file);

       $f = fopen("log.txt","a+");
       if ($f)
         {
         fwrite ($f,time()."Finished\n");
         fclose($f);
         }
   }
   else 
   {

     header ("HTTP/1.1 404 Not found");
   }
  }
 exit; 
}
?>
<!DOCTYPE html
        PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="base.css">
<script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
    <script src="//www.gstatic.com/cast/sdk/libs/caf_receiver/v3/cast_receiver_framework.js"></script>
    <!-- Cast Debug Logger -->
    <script src="//www.gstatic.com/cast/sdk/libs/devtools/debug_layer/caf_receiver_logger.js"></script>
    <meta http-equiv="Content-Type" content="text/html; Charset=windows-1251"/>
    <title>My Remote Library</title>
<style>
a {
text-decoration:none;
color: inherit;
}
</style>
</head>
<body>

<!--for better view in browser-->
<div class="bg"></div>
<div class="wrap" id="browser">
 <ul class="navigation-items"></ul>
</div>

<cast-media-player></cast-media-player>
                      
<script type='text/javascript'>

function onPlayerStateChange(event){
 if (event.data==0) VideoEnded();

}

String.prototype.toHHMMSS = function () {
    var sec_num = parseInt(this, 10); // don't forget the second param
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);

    if (hours   < 10) {hours   = "0"+hours;}
    if (minutes < 10) {minutes = "0"+minutes;}
    if (seconds < 10) {seconds = "0"+seconds;}
    var time    = hours+':'+minutes+':'+seconds;
    return time;
}

TimeWait = 1000*60*31;
Timer=0;

$(document).keydown(onkeydown);

function onkeydown(event)
{                                                             
if (event.keyCode==39)    // left
{
 if ($(".navigation-item.focus").next().length)
 {
  if (player.getPlayerState()=="PLAYING")
   Next();
else
{
  $el = $(".navigation-item.focus");
  $el.removeClass('focus');
  $el.next().addClass('focus');
}

 }                        
}
else
if (event.keyCode==37)  // right
{
 if ($(".navigation-item.focus").prev().length)
 {
  if (player.getPlayerState()=="PLAYING")
    Prev();
else
{
  $el = $(".navigation-item.focus");
  $el.removeClass('focus');
  $el.prev().addClass('focus');
}

 }    
} 
else
if (event.keyCode==38)
{
 // up
 if ($(".navigation-item.focus").prevAll().length>=5)
 {
  $el = $(".navigation-item.focus");
  $el.removeClass('focus');
  $($el.prevAll()[4]).addClass('focus');

 }
}                   
else
if (event.keyCode==40)
{
 // down
 if ($(".navigation-item.focus").nextAll().length)
 {
  $el = $(".navigation-item.focus");
  $el.removeClass('focus');
  $($el.nextAll()[Math.min($el.nextAll().length-1,4)]).addClass('focus');

 }
}     
else
if (event.keyCode==13)
{
   if (Timer!=null) clearTimeout(Timer);
   Timer=0;
   StopNext=false;

 if (player.getPlayerState()=="IDLE")
 {
 if ($(".navigation-item.focus").attr("play"))
 {
  Play($(".navigation-item.focus").attr("data"),$(".navigation-item.focus"));
/*
  if (decodeURI($(".navigation-item.focus").attr("data")).toLowerCase().indexOf("ÏÛÎ¸ÚÙËÎ¸Ï")>0)
  {
   Timer = setTimeout(TimerStop,TimeWait);
  }
*/
 }
 else
  $(".navigation-items").load("?browse&Folder="+$(".navigation-item.focus").attr("data"));
 }
 else 
 if (player.getPlayerState()=="PAUSED")
 {
   Resume();
 }
 else if (player.getPlayerState()=="PLAYING")
   Pause();

}  
else
if ((event.keyCode==461) || (event.keyCode==27) || (event.keyCode==413)) // back
{
 if (player.getPlayerState()=="IDLE")
 {
  if ($("#back_ref").length)
   $(".navigation-items").load("?browse&Folder="+$("#back_ref").attr("data"));
 }
 else
  Stop();

} 
else
if (event.keyCode==19)
{
 // NRY pause timer
 Pause();
}
else
if (event.keyCode==415)
{
 Resume();
}
else
if (event.keyCode==412)
{
// if (YouTubePlaying)
//  YouPlayer
// else
 $("#media")[0].pause();

 if (player.getPlaybackRate()>0) $("#media")[0].playbackRate = -1;
 else
  if ($("#media")[0].playbackRate<=-8)
    $("#media")[0].playbackRate*=2;

 $("#media")[0].play();
 
}
else
if (event.keyCode==417)
{
// $("#media")[0].pause();
 var r =$("#media")[0].playbackRate;
 $("#media")[0].playbackRate=2;

 $("#media")[0].play();

  var p = $("<div>Speed: "+r+" "+$("#media")[0].playbackRate+"</div>"); 
  p[0].className="TimerDiv";
  document.body.appendChild(p[0]);
  setTimeout(function(){p.remove();},5000);

}
else
if (event.keyCode==425) Next(); // Next
else if (event.keyCode==424) Prev();  // Prev
else
if ((event.keyCode==457) || (event.keyCode==192)) //Info or ~
{ // Info

  var p = $("<div>"+($("#media")[0].currentTime*100/$("#media")[0].duration).toFixed(0)+"% "+$("#media")[0].currentTime.toString().toHHMMSS()+" / "+$("#media")[0].duration.toString().toHHMMSS()+"</div>"); 

  p[0].className="TimerDiv";
  document.body.appendChild(p[0]);
  setTimeout(function(){p.remove();},5000);
                         
}
else
{
 Display(event.keyCode);
}           

// else
 //  $(".navigation-item.focus")[0].innerHTML=event.keyCode;

// 461 - Back
// 415 - play
// 413 - stop
// 19 pause
// 417 - >>
// 412 <<
// 425 >>|
// 424 |<<

  if ($(".navigation-item.focus").length==0)
    $(".navigation-item").first().addClass("focus");

   var page = Math.floor($(".navigation-item.focus")[0].offsetTop/718);
   $(".navigation-items").scrollTop(page*720);
  

}

function beep() {
    var snd = new Audio("data:audio/wav;base64,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qJrx4KOQjahZxWbcZgztj2c49nKmkId44S71j0c8eV9yDK6uPRzx5X18eDvjvQ6yKo9ZSS6l//8elePK/Lf//IInrOF/FvDoADYAGBMGb7FtErm5MXMlmPAJQVgWta7Zx2go+8xJ0UiCb8LHHdftWyLJE0QIAIsI+UbXu67dZMjmgDGCGl1H+vpF4NSDckSIkk7Vd+sxEhBQMRU8j/12UIRhzSaUdQ+rQU5kGeFxm+hb1oh6pWWmv3uvmReDl0UnvtapVaIzo1jZbf/pD6ElLqSX+rUmOQNpJFa/r+sa4e/pBlAABoAAAAA3CUgShLdGIxsY7AUABPRrgCABdDuQ5GC7DqPQCgbbJUAoRSUj+NIEig0YfyWUho1VBBBA//uQZB4ABZx5zfMakeAAAAmwAAAAF5F3P0w9GtAAACfAAAAAwLhMDmAYWMgVEG1U0FIGCBgXBXAtfMH10000EEEEEECUBYln03TTTdNBDZopopYvrTTdNa325mImNg3TTPV9q3pmY0xoO6bv3r00y+IDGid/9aaaZTGMuj9mpu9Mpio1dXrr5HERTZSmqU36A3CumzN/9Robv/Xx4v9ijkSRSNLQhAWumap82WRSBUqXStV/YcS+XVLnSS+WLDroqArFkMEsAS+eWmrUzrO0oEmE40RlMZ5+ODIkAyKAGUwZ3mVKmcamcJnMW26MRPgUw6j+LkhyHGVGYjSUUKNpuJUQoOIAyDvEyG8S5yfK6dhZc0Tx1KI/gviKL6qvvFs1+bWtaz58uUNnryq6kt5RzOCkPWlVqVX2a/EEBUdU1KrXLf40GoiiFXK///qpoiDXrOgqDR38JB0bw7SoL+ZB9o1RCkQjQ2CBYZKd/+VJxZRRZlqSkKiws0WFxUyCwsKiMy7hUVFhIaCrNQsKkTIsLivwKKigsj8XYlwt/WKi2N4d//uQRCSAAjURNIHpMZBGYiaQPSYyAAABLAAAAAAAACWAAAAApUF/Mg+0aohSIRobBAsMlO//Kk4soosy1JSFRYWaLC4qZBYWFRGZdwqKiwkNBVmoWFSJkWFxX4FFRQWR+LsS4W/rFRb/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////VEFHAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAU291bmRib3kuZGUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMjAwNGh0dHA6Ly93d3cuc291bmRib3kuZGUAAAAAAAAAACU=");  
    snd.play();
}

function Display(msg){
  var p = $("<div>Key: "+msg+"</div>"); 
  p[0].className="TimerDiv";
  document.body.appendChild(p[0]);
  setTimeout(function(){p.remove();},5000);

}

function TimerStop()
{
  var p = $("<div>¬–≈Ãﬂ ¬€ÿÀŒ</div>"); 
  p[0].className="TimerDiv";
  document.body.appendChild(p[0]);
 // beep();
  setTimeout(function(){p.remove();},5000);
  StopNext=true;
}

function VideoEnded()
{
if (!StopNext)
 Next();
else
{
//  var p = $("<div>œ–Œ—ÃŒ“– Œ ŒÕ◊≈Õ</div>"); 
//  p[0].className="TimerDiv";
//  document.body.appendChild(p[0]);
//  StopNext=false;
  document.location.href='end.html';
 }
}

function Next()
{
 if ($(".navigation-item.focus").next().length)
 {
   $el = $(".navigation-item.focus");
   $el.removeClass("focus");
   $el.next().addClass("focus");
   Play($(".navigation-item.focus").attr("data"),$(".navigation-item.focus"));

   var page = Math.floor($(".navigation-item.focus")[0].offsetTop/718);
   $(".navigation-items").scrollTop(page*720);

 }
 else
  Stop();

}

function Prev()
{
 if ($(".navigation-item.focus").prev().length)
 {
   $el = $(".navigation-item.focus");
   $el.removeClass("focus");
   $el.prev().addClass("focus");
   Play($(".navigation-item.focus").attr("data"),$(".navigation-item.focus"));

   var page = Math.floor($(".navigation-item.focus")[0].offsetTop/718);
   $(".navigation-items").scrollTop(page*720);

 }
 else
  Stop();
}

function Stop()
{
 $("#browser").show();
// var player =  $("#media")[0];
 player.stop();
}

function Play(file,elem)
{     
 var req = new cast.framework.messages.LoadRequestData();
 req.autoplay =  true;
 req.media = new cast.framework.messages.MediaInformation();
 req.media.contentId = file;
 req.media.contentUrl = "http://<?=$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]?>?play&path="+file;
 if (file.toLowerCase().endsWith(".mp4")) req.media.contentType = 'video/mp4';
else
 if (file.toLowerCase().endsWith(".mkv")) req.media.contentType = 'video/x-matroska';
else
 if (file.toLowerCase().endsWith(".avi")) req.media.contentType = 'video/avi';
else
 if (file.toLowerCase().endsWith(".ts")) req.media.contentType = 'video/mp2t';
 req.media.streamType = cast.framework.messages.StreamType.BUFFERED;
 player.load(req);
 player.play();
}

function Resume(){
 player.play();
}

function Pause(){
 player.pause();
}

function item_click(elt){

   if (Timer!=null) clearTimeout(Timer);
   Timer=0;
   StopNext=false;

   $el = $(".navigation-item.focus");
   $el.removeClass('focus');
   $(elt).addClass('focus');


 if ($(".navigation-item.focus").attr("play"))
 {
   Play($(".navigation-item.focus").attr("data"),$(".navigation-item.focus"));
 }
 else
  $(".navigation-items").load("?browse&Folder="+$(".navigation-item.focus").attr("data"));


}

$(document).ready(function(){

 $(".navigation-items").load("?browse");

});

const context = cast.framework.CastReceiverContext.getInstance();
const player = context.getPlayerManager();

const playerData = {};
const playerDataBinder = new cast.framework.ui.PlayerDataBinder(playerData);

context.addCustomMessageListener('urn:x-cast:com.myremotelibrary.commands',(e)=>{
  onkeydown({keyCode:e.data.command});
});


player.addEventListener(
     cast.framework.events.EventType.ERROR,
    e => {
     Display(e.detailedErrorCode+" "+e.error);
     console.Log(JSON.stringify(e));
   });

player.addEventListener(
     cast.framework.events.EventType.ENDED,
    e => {
     VideoEnded();
   });

// Update ui according to player state
playerDataBinder.addEventListener(
    cast.framework.ui.PlayerDataEventType.STATE_CHANGED,
    e => {
      switch (e.value) {
        case cast.framework.ui.State.LAUNCHING:
        case cast.framework.ui.State.IDLE:
          // Write your own event handling code
           $("#browser").show();
          break;
        case cast.framework.ui.State.LOADING:
          // Write your own event handling code
        case cast.framework.ui.State.BUFFERING:
          // Write your own event handling code
        case cast.framework.ui.State.PAUSED:
          // Write your own event handling code
        case cast.framework.ui.State.PLAYING:
          // Write your own event handling code
           $("#browser").hide();
          break;
      }
    });

context.start(
 {
  customNamespaces : 
   {
    'urn:x-cast:com.myremotelibrary.commands' : cast.framework.system.MessageType.JSON
   },
  maxInactivity : 5600
 });

</script> 

</body>
</html> 

