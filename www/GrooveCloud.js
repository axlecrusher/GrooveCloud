	var myPlaylist;
	var resultList;
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
			$( "#addNotesForm" ).hide();
		});
	});
 
	$("#notes-cancel").click(function() {
		$( "#addNotesForm" ).hide();
	});

});

function doSearch()
{
	$('#content #results').stop(true,true);
	$('#content #spinner').stop(true,true);
	$('#content #results').fadeOut(100, 'swing', function(){$('#spinner').fadeIn(100);});
//	$("#content #results").load("Search.php?s=" + encodeURIComponent($("#search").val()), showResults);
//	$("#content #results").load("Search.php?s=" + encodeURIComponent($("#search").val()), showResultsJson);
//	jQuery.getJSON( "Search.php?s=" + encodeURIComponent($("#search").val()), showResultsJson )
	jQuery.get( "Search.php?s=" + encodeURIComponent($("#search").val()), showResultsJson )
}

function showResultsJson(data, textStatus, XMLHttpRequest)
{
		if (textStatus == "success")
		{
			$('#content #results').stop(true,true);
			$('#content #spinner').stop(true,true);
			$('#spinner').fadeOut(100,'swing', function(){$('#content #results').fadeIn(100);} );
			resultList = jsonh.parse(data);
			DrawTableFromJson(resultList);
		}
}

function DrawTableFromJson(data)
{
	var table = $("#resultTable");
	table.empty();

	var l = data.length;
	for (i=0; i<l; i++)
	{
		var media = data[i];
	
		var html = '<tr><td><a href="javascript:addSong('+i+');">' + media['title'] + '</a></td>'
			+ '<td>' + media['album'] + '</td>' + '<td>' + media['artist'] + '</td>'
			+ '<td><a class="addNoteButton" href="javascript:showNotes('+i+');" title="Edit Notes">Add Notes</a></td></tr>';
		table.append(html);
	}
}

function addSong(index)
{
	var media = resultList[index];
	var path = media['path'];
	var type = path.substring(path.lastIndexOf(".")+1);
	media[type] = path;

	myPlaylist.add(media);
	$( ".jp-playlist ul" ).sortable();
	$( ".jp-playlist ul" ).disableSelection();
}

function showNotes(index)
{
	var media = resultList[index];
	$('#noteSerial').val(media['serial']);
	$('#noteHash').val(media['hSerial']);
	$('#notes').val('');
	$('#addNotesForm').show();
	$('#noteTitle').text(media['title']);

	var data = $( "#addNotesForm" ).serialize();
	thxr = $.post('GetNote.php', data, function(data, status, jqXHR)
	{
		if (thxr === jqXHR)
		{
			$('#notes').val(data);
		}
	});
}
