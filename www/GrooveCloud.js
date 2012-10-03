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

function doSearch(terms)
{
	$('#content #results').stop(true,true);
	$('#content #spinner').stop(true,true);
	$('#content #results').fadeOut(100, 'swing', function(){$('#spinner').fadeIn(100);});
//	$("#content #results").load("Search.php?s=" + encodeURIComponent($("#search").val()), showResults);
//	$("#content #results").load("Search.php?s=" + encodeURIComponent($("#search").val()), showResultsJson);
//	jQuery.getJSON( "Search.php?s=" + encodeURIComponent($("#search").val()), showResultsJson )
	if (terms == null)
	{
		terms = $("#search").val();
	}
	jQuery.get( "Search.php?s=" + encodeURIComponent(terms), showResultsJson )
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
	
		var html = '<tr><td>'+media['trackNumber']+'</td><td><a href="javascript:addSong('+i+');">' + media['title'] + '</a></td>'
			+ '<td><a href="javascript:searchAlbum('+i+');">' + media['album'] + '</a></td>'
			+ '<td><a href="javascript:searchArtist('+i+');">' + media['artist'] + '</a></td>'
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

function searchAlbum(index)
{
	var media = resultList[index];
	doSearch(media['album']);
}

function searchArtist(index)
{
	var media = resultList[index];
	doSearch(media['artist']);
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

/* Copyright (c) 2012 Joshua Allen

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
