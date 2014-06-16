//<editor-fold desc="Templates">
String.prototype.format = function() {
    var s = this,
        i = arguments.length;

    while (i--) {
        s = s.replace(new RegExp('\\{' + i + '\\}', 'gm'), arguments[i]);
    }
    return s;
};
//</editor-fold>

//<editor-fold desc="Model-Objects">
function Line()
{
    this.__type__ = 'Line';
    this.id = 0;
    this.number = 0;
    this.destination = 0;
    this.destinationName = '';
    this.operator = 0;
    this.eta = 0;
}
function Station()
{
    this.__type__ = 'Station';
    this.id = 0;
    this.name = '';
    this.alias = '';
    this.description = '';
    this.distance = '';
}
//</editor-fold>

//<editor-fold desc="UI-Controls">
function ListBox(id)
{
    var _this = this;
    this.id = id;
    this.onRowClick = function (row) {};
    /**
     *
     * @type {ListBoxRow}
     */
    this.rows = [];
    this.clear = function () {
        this.rows = [];
        this.render();
    };
    this.render = function() {
        $(this.id).html('');
        for(var i=0;i<this.rows.length;i++) {
            var id = 'table-' + this.id.replace('#','') + '-row-' + i;
            var content = '<tr id="' + id + '"><td>' + this.rows[i].render() + '</td></tr>';
            $(this.id).append(content);
            this.rows[i].setItemId(id);
            this.rows[i].onClick = this.rowClickHandler;
        }
    };
    this.add = function (rowAdapter) {
        rowAdapter.onClick = this.rowClickHandler;
        this.rows.push(rowAdapter);
    };
    this.rowClickHandler = function (row) {
        _this.onRowClick(row);
    };
}
function ListBoxRow()
{
   this.id = 0;
   this.onClick = function () {};
   this.render = function () { return ''; };
   this._this = this;
   this.setItemId = function (id) {
     this.id= '#' + id;
       var _this = this;
     $(this.id).click(function() {
         if(_this.onClick !== false)
            _this.onClick(_this._this);
     });
   };

   //bind to this item.
}
function TextBox(id)
{
    this.id = id;
    this.getValue = function()      {   return $(this.id).val();    };
    this.setValue = function(value) {   $(this.id).val(value);      };
    this.clear    = function()      {   $(this.id).val('');         };
    this.setPlaceholder = function(value) { $(this.id).attr('placeholder',value); console.log('placeholder is now set to ' + value)};

}
function Button(id, onClick)
{
    var _this = this;
    this.id = id;

    this.show = function () { $(this.id).show();};
    this.hide = function () { $(this.id).hide();};

    this.onClick = onClick || function(button) {};
    //bind on click
    $(id).click(function() {
     if(_this.onClick)
        _this.onClick(_this);
     else
        console.log('Button ' + this.id + ' onClick event is not binded');

    });
}

function Label(id)
{
    this.id = id;
    this.getValue    = function()      {   return $(this.id).html();                                        };
    this.setValue    = function(value) {   $(this.id).html(value);                                          };
    this.clear       = function()      {   $(this.id).html('');                                             };
    this.offsetTop   = function()      {   return $(this.id).offset().top + $(this.id).outerHeight(true);   };

}
//</editor-fold>

//<editor-fold desc="UI-Panels">
function ToolbarPanel()
{
    this.searchButton = new Button('#toolbar-search');
    this.faveButton = new Button('#toolbar-fave');
    this.refreshButton = new Button('#toolbar-refresh');
    this.settingsButton = new Button('#toolbar-settings');
}
function SearchPanel()
{
    var _this = this;
    this.id = '#search-panel';
    this.searchTextBox = new TextBox('#search-text');
    this.searchButton = new Button('#search-button');
    this.onSearchEvent = function (searchQuery) {};
    this.show = function  ()    { $(this.id).show(); };
    this.hide = function  ()    { $(this.id).hide(); };
    this.clear = function ()    { this.searchTextBox.clear(); };
    this.setPlaceholder = function (value) { this.searchTextBox.setPlaceholder(value); };
    this.searchButton.onClick = function () {
        var searchQuery = _this.searchTextBox.getValue();
        _this.onSearchEvent(searchQuery);
    };
    this.bindOnSearchEvent = function (event) {
      this.onSearchEvent = event;
    };
}
function SearchMenu()
{
    var _this = this;
    this.id = '#search-menu';
    this.isVisible = false;
    this.searchByStationId = new Button('#search-menu-button-line');
    this.searchByLineNumber = new Button('#search-menu-button-bus');
    this.searchNearby = new Button('#search-menu-button-nearby');
    this.showSearchDelegate = function () {};

    this._construct = function () {
        _this.searchByStationId.onClick = function () { _this.showSearchDelegate('STATION_ID');};
        _this.searchByLineNumber.onClick = function () { _this.showSearchDelegate('LINE_NUM');};
        _this.searchNearby.onClick = function () { _this.showSearchDelegate('NEAR_BY');};
        
    };
    this.toggle = function () {
        if(_this.isVisible)
            _this.hide();
        else
            _this.show();
    };
    this.show = function () {
        _this.isVisible = true;
        $(_this.id).show();
        $(_this.id).removeClass('pop-out');
        $(_this.id).addClass('pop-in');

    };
    this.hide = function () {
        _this.isVisible = false;
        $(_this.id).removeClass('pop-in');
        $(_this.id).addClass('pop-out');
        $(_this.id).one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', _this.onAnimationFinished);
    };
    this.onAnimationFinished = function () {
        $(_this).hide();
    };
    this._construct();
}
function ListPanel()
{
    var _this  = this;
    this.listBox = new ListBox('#station-list');
    this.listTitle = new Label('#station-title');
    
    this.faveButton = new Button('#station-fave');
    this.faveButtonState = false;
    
    this.faveButtonAction = function () {};

    this.bindListClickAction = function(action) {
      this.listBox.onRowClick = action;
    };

    this.bindFaveButtonClickAction = function(action) {
        this.faveButtonAction = action;    
    };

    this.faveButton.onClick = function () {
            _this.faveButtonState = !_this.faveButtonState;
            _this.setFaveButtonState(_this.faveButtonState);
            if(_this.faveButtonAction)
                _this.faveButtonAction(_this.faveButtonState);
    }

    this.clear = function () {
        this.listBox.clear();
        this.listTitle.clear();

    };
    this.setTitle = function (title) {
        this.listTitle.setValue(title);
    };
    this.resetListSize = function () {
        $('.stations_container').offset({top: this.listTitle.offsetTop()});
    };
    this.showFaveButton = function() {
        this.faveButton.show();
    };
    this.hideFaveButton = function() {
        this.faveButton.hide();
    };
    this.setFaveButtonState = function(state) {
        this.faveButtonState = state;
        if(state)
        {
            $(this.faveButton.id).removeClass('unfaved_station');
            $(this.faveButton.id).addClass('faved_station');
        }
        else
        {
            $(this.faveButton.id).removeClass('faved_station');
            $(this.faveButton.id).addClass('unfaved_station');
        }
    };

}
function LoaderPanel()
{
    this.id = '#loader';
    this.show = function  ()    { $(this.id).show(); };
    this.hide = function  ()    { $(this.id).hide(); };
}
function ModalView() {
    
    this.id = '#modal-view';
    this.isVisible = false;
    var _this = this;

    this.show = function () {
        this.isVisible = true;
        $(this.id).addClass('modal-view-unvisable');
        
        $(this.id).bind('show', function() {
            alert('c');
            //$(this.id).removeClass('modal-view-unvisable');
            //$(this.id).addClass('modal-view-visable');
        });
        $(this.id).show();    
            
        
    };

    this.hide = function () {
        this.isVisible = false;
        $(this.id).removeClass('modal-view-visable');
        $(this.id).addClass('modal-view-unvisable');
        $(this.id).one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', _this.onAnimationFinished);
    };

    this.toggle = function() {
        if(this.isVisible) this.hide();
        else this.show();
    };

    this.onAnimationFinished = function () {
        $(_this.id).hide();
    };


}
//</editor-fold>

//<editor-fold desc="UI-Adapters">
var ListBoxAdapterFactory = {
    createAdapter: function (object) {
        var type = object.__type__;
        var adapterName = type + 'ListBoxRowAdapter';
        var adapter = eval(adapterName);
        if(adapter == undefined)
        {
            console.log("Could not create ListBoxRowAdapter for __type__ " + type + ", please make sure that " + adapterName + " is a ListBoxAdapter");
            return false;
        }
        return new adapter(object);
    }
};
function StationListBoxRowAdapter(station)
{

    //id,name,desc,id
    this.stationTemplate = '<div class="station_container" id="{0}"><div class="station_name">{1}</div><div class="station_location">{2}</div><div class="station_number">מספר תחנה {3}</div></div>';
    this.station = station;
    var self = new ListBoxRow();
    var _this = this;
    self._this = this;

    self.render = function () {
        return _this.stationTemplate.format( _this.station.id,
                                _this.station.name,
                                _this.station.description,
                                _this.station.id);
    };

    return self;
}
function NearbyListBoxRowAdapter(nearbyStation)
{
    //id,name,desc,distance,id
    this.nearbyTemplate = '<div class="station_container" id="{0}"><div class="station_name">{1}</div><div class="station_location">{2}</div><div class="station_number">מרחק: {3} קמ<br/>מספר תחנה {4}</div></div>';
    this.station = nearbyStation;
    var self = new ListBoxRow();
    var _this = this;
    self._this = this;

    self.render = function () {
        return _this.nearbyTemplate.format( _this.station.id,
                                _this.station.name,
                                _this.station.description,
                                _this.station.distance,
                                _this.station.id);
    };

    return self;
}
function LineListBoxRowAdapter(line)
{
    //id,operator,name,desc,eta
    this.lineTemplate = '<div class="line_container" id="{0}"><div class="station_operator" style="background-image: url(\'img/operators/{1}.png\');"></div><div class="station_name">{2}</div><div class="station_location">{3}</div>{4}</div>';
    this.etaNowTemplate = '<div class="line_time">מגיע עכשיו</div>';
    this.etaTimeTemplate = '<div class="line_time">מגיע בעוד <span class="line_min">{0}</span> דק\'</div>';

    this.line = line;
    var self = new ListBoxRow();
    var _this = this;
    self._this = this;

    self.render = function () {
        var etaTemplate;
        if(line.eta > 1)
            etaTemplate = _this.etaTimeTemplate.format(_this.line.eta);
        else
            etaTemplate = _this.etaNowTemplate;
        return _this.lineTemplate.format(_this.line.id,
                            _this.line.operator,
                            _this.line.number,
                            _this.line.destinationName,
                            etaTemplate);
    };

    return self;
}
//</editor-fold>

function WimbData()
{
    var _this = this;
    this.fave = [];
    this.lastOperation = 0;
    this.lastOperationArguments = [];
    this.currentTitle = '';
    this.currentLat = 0;
    this.currentLng = 0;
    this.isLocationUpdated = false;

    this.get = function(url, callback) {
        console.log('requesting data from ' + url);
        var getObj = $.get(url, function (data){
            console.log('data received.');
            callback(data);
        })
            .fail(function () {
           alert('Could not connect to server, please try again later.');
        });
    };
    
    this.getCurrentTitle = function() {return this.currentTitle;};
    
    this.onOperationFinish = function() {};
    this.fetchFaveStations = function (force) {
        this.lastOperation = _this.fetchFaveStations;
        this.lastOperationArguments = false;
        this.currentTitle = 'התחנות שלי';
        this.currentStation = false;
        var faves = localStorage.getItem("fave");
        this.fave = JSON.parse(faves);

        if(this.fave == null || force) {
              this.get('ajax/stations.php',function (stations) {
                  _this.fave = stations;
                  _this.saveFaveList();
                  _this.onOperationFinish(_this.fave);
              });
        }
        else {
              console.log('stations are loaded to memory');
              this.onOperationFinish(this.fave);
        }
    };

    this.fetchLineETA = function(stationId) {
        this.lastOperation = _this.fetchLineETA;
        this.lastOperationArguments = stationId;
        this.get('ajax/lines.php?stationId=' + stationId, function (data) {
           _this.currentTitle = data.station.name;
           _this.onOperationFinish(data.eta);
        });
    };

    this.fetchStation = function (stationId, callback) {
        for(var i=0;i<_this.fave.length;i++)
        {
            if(_this.fave[i].id == stationId)
            {
                console.log('fetched station info from fave');
                callback(_this.fave[i]);
                return;
            }
        }
        console.log('could not find station info in fave, looking up online');
        this.get('ajax/station.php?stationId=' + stationId, function (data) { callback(data); });
    };


    this.saveFaveList = function () {
        localStorage.setItem("fave", JSON.stringify(this.fave)); 
    };

    this.faveStation = function (stationId) {
        this.fetchStation(stationId, function (station) {
            _this.fave.push(station);
            _this.saveFaveList();
        });
    };

    this.unfaveStation = function (stationId) {
        for(var i=0;i<this.fave.length;i++)
        {
            if(this.fave[i].id == stationId)
            {
                this.fave.splice(i, 1);
            }
        }
        _this.saveFaveList();
    };

    this.invokeLastOperation = function() {
        if(_this.lastOperation)
            _this.lastOperation(_this.lastOperationArguments);
    };

    this.isLastOperationAvalible = function () {
        if(_this.lastOperation) return true;
        return false;
    };

    this.resetLastOperation = function () {
        _this.lastOperation = 0;
        _this.lastOperationArguments = [];
    };

    this.fetchNearbyStations = function () {
        _this.currentTitle = 'תחנות קרובות';
        _this.getLocation(function () {
            if(_this.isLocationUpdated)
            {
                console.log(_this.currentLat + " " + _this.currentLng);
                _this.get('ajax/nearby.php?lat=' + _this.currentLat + "&lng=" + _this.currentLng, function (data) {
                    _this.currentTitle = 'תחנות קרובות';
                    _this.onOperationFinish(data)
                });
            }
            else
            {
                _this.onOperationFinish([]); //error; return empty set
            }
        });
    };

    this.isStationFaved = function(stationId) {
        for (var i = 0; i < _this.fave.length; i++) {
            if(_this.fave[i].id == stationId) return true;
        }
        return false;
    };

    this.getLocation = function (locationFetchedCallback) {
        if(navigator.geolocation)
        {
            navigator.geolocation.getCurrentPosition(function (position) {
                _this.currentLat = position.coords.latitude;
                _this.currentLng = position.coords.longitude;
                _this.isLocationUpdated = true;
                locationFetchedCallback();
            }, function (error) {
                alert('שירותי מיקום לא זמינים');
                _this.isLocationUpdated = false;
                console.log("Location service failed with error " + error.code);
                locationFetchedCallback();
            });
        }
        else
        {
            alert('שירותי מיקום לא זמינים');
            _this.isLocationUpdated = false;
            locationFetchedCallback();
        }
    };
}


function WimbUI()
{
    var _this = this; //keep reference of this in delegate actions
    _this.dataSource = new WimbData();
    _this.toolbarPanel = new ToolbarPanel();
    _this.searchMenu = new SearchMenu();
    _this.searchPanel = new SearchPanel();
    _this.listPanel = new ListPanel();
    _this.loader = new LoaderPanel();
    _this.modalView = new ModalView();

    _this.construct = function() {
        _this.bindToolbarButtons();
        _this.dataSource.onOperationFinish = _this.dataSourceOperationFinish;
        _this.showFave();
    };

    _this.dataSourceOperationFinish = function (data) {
        _this.listPanel.clear();
        _this.listPanel.setTitle(_this.dataSource.getCurrentTitle());
        _this.listPanel.resetListSize();
        for(var i=0;i<data.length;i++) {
            _this.listPanel.listBox.add(ListBoxAdapterFactory.createAdapter(data[i]));
        }
        _this.listPanel.listBox.render();
        _this.loader.hide();
    };

    _this.bindToolbarButtons = function () {
        _this.searchMenu.showSearchDelegate = _this.showSearch;
        _this.toolbarPanel.searchButton.onClick = _this.searchMenu.toggle;
        _this.toolbarPanel.faveButton.onClick = _this.showFave; 
        _this.toolbarPanel.refreshButton.onClick = _this.refresh;
        _this.toolbarPanel.settingsButton.onClick = _this.showSettings;
    };

    _this.showSettings = function () {
        _this.modalView.toggle();

    };

    _this.resetView = function () {
        _this.loader.hide();
        _this.searchPanel.clear();
        _this.listPanel.clear();
        _this.listPanel.hideFaveButton();
        _this.searchMenu.hide();
        _this.searchPanel.hide();
    };

    _this.showSearch = function(type) {
        _this.dataSource.resetLastOperation();
        _this.resetView();
        
        if(type==='STATION_ID')
        {
            _this.listPanel.setTitle('חיפוש');
            _this.searchPanel.show();
            _this.searchPanel.bindOnSearchEvent(function (searchQuery) { _this.loadStationEta(searchQuery);});
            _this.searchPanel.setPlaceholder('חפש מספר תחנה');
            _this.listPanel.bindListClickAction(function () {});
        }
        else if(type==='LINE_NUM')
        {
            _this.listPanel.setTitle('חיפוש');
            _this.searchPanel.show();
            _this.searchPanel.setPlaceholder('חפש מספר אוטובוס');   
            _this.listPanel.bindListClickAction(function () {});
        }
        else if(type==='NEAR_BY')
        {
            _this.listPanel.setTitle('תחנות קרובות');
            _this.loader.show();
            _this.dataSource.fetchNearbyStations();
        }
        
        _this.listPanel.resetListSize();
        
    };

    _this.showFave = function() {
        _this.resetView();
        _this.loader.show();
        _this.dataSource.fetchFaveStations();
        _this.listPanel.bindListClickAction(function (row) { _this.loadStationEta(row.station.id)});
    };

    _this.refresh = function() {
        if(_this.dataSource.isLastOperationAvalible())
        {
            _this.loader.show();
            _this.dataSource.invokeLastOperation();
        }
    };

    _this.loadStationEta = function(stationId) {
        var isStationFaved = _this.dataSource.isStationFaved(stationId);
        _this.loader.show();
        _this.dataSource.fetchLineETA(stationId);
        _this.listPanel.showFaveButton();
        _this.listPanel.setFaveButtonState(isStationFaved);
        _this.listPanel.bindFaveButtonClickAction(function (state) {
            if(state)
            {
                _this.dataSource.faveStation(stationId);
            }
            else
            {
                _this.dataSource.unfaveStation(stationId);
            }
        });
        _this.listPanel.bindListClickAction(function () {});

    };

    _this.construct();
}