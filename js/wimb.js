var SCREEN_MODE = {'stations_select' : 0,'station_view':1};
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
}
function search()
{

}
function fave()
{
	getStations();
}
function loadReports()
{
        $('#station_list').html("");
        showLoader();
        var g = $.get('ajax/info.php?stop_code=' + station_id, function (data) {
        	for(i=0;i<data.length;i++)
        	{
        		templateLine(data[i]);
        	}
        	hideLoader();
        	
        });
        g.fail( function () {
        	hideLoader();
        	alert('An error occurred, please try again later.');

        });
        window.screenMode = SCREEN_MODE.station_view;
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
	$('#station_list').html("");
	$.get('ajax/stations.php',function (stations) {
		for(i=0;i<stations.length;i++)
		{
			templateStation(stations[i]);
			hideLoader();
		}
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
	var template= '	<tr><td> ' +
							'<div class="line_container" id="'+ line.id +'">' +
							'<div class="station_operator" style="background-image: url(\'img/operators/' + line.operator + '.png\');"></div>' +
							'<div class="station_name">' +  line.line_number +'</div>' +
							'<div class="station_location">'+ line.dest_desc +'</div>' +
							'<div class="line_time">מגיע בעוד <span class="line_min">'+ line.arrive +'</span> דק\'</div>' +
						'</div>' +
					'</td></tr>';
	$('#station_list').append(template);
}