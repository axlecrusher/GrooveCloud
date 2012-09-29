<?php
	require "Sessions.php";
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link type="text/css" href="skin/jplayer.blue.monday.css" rel="stylesheet" />
	<link type="text/css" href="skin/midnight.black/jplayer.midnight.black.css" rel="stylesheet" />
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>
	<script type="text/javascript" src="jquery.jplayer.min.js"></script>
	<script type="text/javascript" src="add-on/jplayer.playlist.drag.js"></script>
	<link type="text/css" href="my.css" rel="stylesheet" />
 <script type="text/javascript">

	var myPlaylist;
    $(document).ready(function(){

	$("#testClick").click(function() {
		alert("Handler for .click() called.");
	});

	$("#searchBtn").click(function() {
	//	alert("Handler for .search() called.");

	doSearch();
 

	});

$('#search').keypress(function (e) {
  if (e.which == 13) {
	doSearch();
  }
});

	myPlaylist = new jPlayerPlaylist({
		jPlayer: "#jquery_jplayer_1",
		cssSelectorAncestor: "#jp_container_1"
	},[],{
		playlistOptions: {
			enableRemoveControls: true
		},
		swfPath: "",
		supplied: "m4a,mp3",
		solution:"flash, html"

	});

$('.jp-playlist ul:last').sortable({
         update: function() {
             myPlaylist.scan();
         }
     });


	$("#notes-save").click(function() {
		var data = $( "#addNotesForm" ).serialize();
		$.post('AddNote.php', data, function(data) {
			$( "#addNodeDialog" ).hide();
		});
	});
 
	$("#notes-cancel").click(function() {
		$( "#addNodeDialog" ).hide();
	});

});

function doSearch()
{
		$('#content #results').stop(true,true);
		$('#content #spinner').stop(true,true);
		$('#content #results').fadeOut(100, 'swing', function(){$('#spinner').fadeIn(100);});
		$("#content #results").load("Search.php?s=" + encodeURIComponent($("#search").val()), showResults);
}

function showResults(responseText, textStatus, XMLHttpRequest)
{
		if (textStatus == "success")
		{
			$('#content #results').stop(true,true);
			$('#content #spinner').stop(true,true);
			$('#spinner').fadeOut(100,'swing', function(){$('#content #results').fadeIn(100);} );
		}
}

function addSong(title,artist,path)
{
	var type = path.substring(path.lastIndexOf(".")+1);
	var media = {title:title,artist:artist};	
	media[type] = path;

	myPlaylist.add(media);
	$( ".jp-playlist ul" ).sortable();
	$( ".jp-playlist ul" ).disableSelection();

}

function showNotes(serial,hash)
{
	$('#noteSerial').val(serial);
	$('#noteHash').val(hash);
	$('#notes').val('');
	$( "#addNodeDialog" ).show();

	var data = $( "#addNotesForm" ).serialize();
	thxr = $.post('GetNote.php', data, function(data, status, jqXHR) {
		if (thxr === jqXHR)
		{
			$('#notes').val(data);
		}
	});
}
  </script>
</head>
	<body>
<br clear="all" />
<div id="header">
		<div id="searchArea">
			<input type="text" value="" maxlength="100" name="searchBox" id="search">
			<a id="searchBtn" href="javascript:;">Search</a>
		</div>
</div>
<div id="content">
	<div id="spinner" style="display:none;"><img src="290.gif" width="64" height="64"/>
	</div>
	<div id="results" style="display:none;">	
	</div>
	<form id="addNotesForm">
		<div class="addNotesDialog" id="addNodeDialog">
			<textarea rows="10" cols="50" id="notes" name="note"></textarea>
			<input type="hidden" id="noteSerial" name="s" value=""/>
			<input type="hidden" id="noteHash" name="h" value=""/>
			<a href="javascript:;" id="notes-save">Save</a>
			<a href="javascript:;" id="notes-cancel">Cancel</a>
		</div>
	</form>
</div>
<div id="jquery_jplayer_1" class="jp-jplayer"></div>
  <div id="jp_container_1" class="jp-audio">
    <div class="jp-type-single">
      <div class="jp-gui jp-interface">
        <ul class="jp-controls">
          <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
          <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
          <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
          <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
          <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
          <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
        </ul>
        <div class="jp-progress">
          <div class="jp-seek-bar">
            <div class="jp-play-bar"></div>
          </div>
        </div>
        <div class="jp-volume-bar">
          <div class="jp-volume-bar-value"></div>
        </div>
        <div class="jp-time-holder">
          <div class="jp-current-time"></div>
          <div class="jp-duration"></div>
          <ul class="jp-toggles">
            <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
            <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
          </ul>
        </div>
      </div>
      <div class="jp-title">
        <ul>
          <li>Bubble</li>
        </ul>
      </div>
<div class="jp-playlist">
    <ul>
      <li></li> <!-- Empty <li> so your HTML conforms with the W3C spec -->
    </ul>
  </div>
      <div class="jp-no-solution">
        <span>Update Required</span>
        To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
      </div>

    </div>
  </div>
	</body>
</html>
