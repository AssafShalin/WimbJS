var SCREEN_MODE = {'stations_select' : 0,'station_view':1, 'search':2};
var screenMode;
var station_id;


$(document).ready(function() {
	getStations();
	$("#station_list").delegate('tr', 'click', function() {	
		if(screenMode == SCREEN_MODE.stations_select)
		{
			station_id = this.children[0].children[0].id;
			loadReports();	
		}
    });
    window.screenMode = SCREEN_MODE.stations_select;
});
function reload()
{
	if(window.screenMode == SCREEN_MODE.stations_select)
	{
		getStations();	
	}
	else if(window.screenMode == SCREEN_MODE.station_view)
	{
		loadReports();
	}
	else if(window.screenMode == SCREEN_MODE.search)
	{
		loadReports();
	}
}
function search()
{
	$('#search_bar').show();
	$('#station_list').html("");
	$('#search_text').val("");
	window.screenMode = SCREEN_MODE.search;
	setTitle('בחר אוטובוס');
}
function searchOnClick()
{
	station_id = $('#search_text').val();
	loadReports();
}
function fave()
{
	getStations();
}
function loadReports()
{
        showLoader();
        var g = $.get('ajax/info.php?stop_code=' + station_id, function (data) {
        	var stop_name = data.stop_name;
        	var eta = data.eta;
        	$('#station_list').html("");
        	setTitle(stop_name);
        	for(i=0;i<eta.length;i++)
        	{
        		templateLine(eta[i]);
        	}
        	hideLoader();
        	
        });
        g.fail( function () {
        	hideLoader();
        	alert('An error occurred, please try again later.');

        });
        window.screenMode = SCREEN_MODE.station_view;
}


function setTitle(title)
{
	var titleText = $('#title');
	titleText.text(title);

	onWindowResize();
}
function showLoader()
{
	$('#loader').show();
}
function hideLoader()
{
	$('#loader').hide();
}
function getStations()
{
	window.screenMode = SCREEN_MODE.stations_select;
	showLoader();
	$.get('ajax/stations.php',function (stations) {
		$('#search_bar').hide();
		$('#station_list').html("");
		setTitle('התחנות שלי');
		for(i=0;i<stations.length;i++)
		{
			templateStation(stations[i]);
		}
		hideLoader();
	});
	
}

function templateStation(station)
{
	var template= '	<tr><td> \
						<div class="station_container" id="'+ station.stop_code + '"> \
							<div class="station_name">'+ station.stop_name +'</div> \
							<div class="station_location">' + station.stop_desc + '</div> \
							<div class="station_number">מספר תחנה ' + station.stop_code + '</div> \
						</div> \
					</td></tr>';
	$('#station_list').append(template);
}
function templateLine(line)
{
	var eta;
	if(line.arrive > 1)				
		eta = '<div class="line_time">מגיע בעוד <span class="line_min">'+ line.arrive +'</span> דק\'</div>';
	else
		eta = '<div class="line_time">מגיע <span class="line_min">עכשיו</span></div>';
	var template= '	<tr><td> ' +
							'<div class="line_container" id="'+ line.id +'">' +
							'<div class="station_operator" style="background-image: url(\'img/operators/' + line.operator + '.png\');"></div>' +
							'<div class="station_name">' +  line.line_number +'</div>' +
							'<div class="station_location">'+ line.dest_desc +'</div>' +
							eta  +
						'</div>' +
					'</td></tr>';

	$('#station_list').append(template);
}


function onWindowResize()
{
	var top = $('#title').offset().top;
	var offset = $('#title').outerHeight(true);
	$('.stations_container').offset({top: top+offset});
}

$( window ).resize(function() {
	onWindowResize();
});
$(document).on('touchmove',function(e){
  
  var isTriggeredByStationContainer = $(e.target).parents('.stations_container').length > 0;
  if(!isTriggeredByStationContainer)
  {
  	e.preventDefault();	
  }
});