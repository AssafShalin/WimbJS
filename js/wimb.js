//<editor-fold desc="Templates">
String.prototype.format = function() {
    var s = this,
        i = arguments.length;

    while (i--) {
        s = s.replace(new RegExp('\\{' + i + '\\}', 'gm'), arguments[i]);
    }
    return s;
};
//id,operator,name,desc,eta
var lineTemplate = '<div class="line_container" id="{0}"><div class="station_operator" style="background-image: url(\'img/operators/{1}.png\');"></div><div class="station_name">{2}</div><div class="station_location">{3}</div>{4}</div>';
var etaNowTemplate = '<div class="line_time">מגיע <span class="line_min">עכשיו</span></div>';
var etaTimeTemplate = '<div class="line_time">מגיע בעוד <span class="line_min">{0}</span> דק\'</div>';

//id,name,desc,id
var stationTemplate = '<div class="station_container" id="{0}"><div class="station_name">{1}</div><div class="station_location">{2}</div><div class="station_number">מספר תחנה {3}</div></div>';

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

}
function Button(id, onClick)
{
    var _this = this;
    this.id = id;
    this.onClick = onClick || function(button) {};
    //bind on click
    $(id).click(function() { _this.onClick(_this);});
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
    this.searchButton.onClick = function () {
        var searchQuery = _this.searchTextBox.getValue();
        _this.onSearchEvent(searchQuery);
    };
    this.bindOnSearchEvent = function (event) {
      this.onSearchEvent = event;
    };
}
function ListPanel()
{
    this.listBox = new ListBox('#station-list');
    this.listTitle = new Label('#station-title');
    this.bindListClickAction = function(action) {
      this.listBox.onRowClick = action;
    };
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
}
function LoaderPanel()
{
    this.id = '#loader';
    this.show = function  ()    { $(this.id).show(); };
    this.hide = function  ()    { $(this.id).hide(); };
}
//</editor-fold>

//<editor-fold desc="UI-Adapters">
function StationListBoxRowAdapter(station)
{
    this.station = station;
    var self = new ListBoxRow();
    var _this = this;
    self._this = this;
    self.render = function () {
        return stationTemplate.format( _this.station.id,
                                _this.station.name,
                                _this.station.description,
                                _this.station.id);
    };

    return self;
}
function LineListBoxRowAdapter(line)
{
    this.line = line;
    var self = new ListBoxRow();
    var _this = this;
    self._this = this;
    self.render = function () {
        var etaTemplate;
        if(line.eta > 1)
            etaTemplate = etaTimeTemplate.format(_this.line.eta);
        else
            etaTemplate = etaNowTemplate;
        return lineTemplate.format(_this.line.id,
                            _this.line.operator,
                            _this.line.number,
                            _this.line.destinationName,
                            etaTemplate);
    };

    return self;
}
var ListBoxAdapterFactory = {
    createAdapter: function (object) {
        var type = object.__type__;
        if(type==='Line') {
            return new LineListBoxRowAdapter(object);
        }
        else if(type === 'Station') {
            return new StationListBoxRowAdapter(object);
        }
        else {
            console.log("Could not create listbox adapter for this object type '{0}'\n".format(type));
        }
    }
};
//</editor-fold>

function WimbData()
{
    var _this = this;
    this.fave = [];
    this.lastOperation = 0;
    this.lastOperationArguments = [];
    this.currentTitle = '';
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
          if(this.fave.length == 0 || force) {
              this.get('ajax/stations.php',function (stations) {
                  _this.fave = stations;
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
    this.invokeLastOperation = function() {
        _this.lastOperation(_this.lastOperationArguments);
    };
    this.resetLastOperation = function () {
        _this.lastOperation = 0;
        _this.lastOperationArguments = [];
    };
}

function WimbUI()
{
    var _this = this; //keep reference of this in delegate actions
    _this.dataSource = new WimbData();
    _this.toolbarPanel = new ToolbarPanel();
    _this.searchPanel = new SearchPanel();
    _this.listPanel = new ListPanel();
    _this.loader = new LoaderPanel();

    _this.construct = function() {
        _this.bindToolbarButtons();
        _this.dataSource.onOperationFinish = _this.dataSourceOperationFinish;
        _this.showFave();

    };
    _this.dataSourceOperationFinish = function (data) {
        _this.resetView();
        _this.listPanel.setTitle(_this.dataSource.getCurrentTitle());
        _this.listPanel.resetListSize();
        for(var i=0;i<data.length;i++) {
            _this.listPanel.listBox.add(ListBoxAdapterFactory.createAdapter(data[i]));
        }
        _this.listPanel.listBox.render();
        _this.loader.hide();
    };
    _this.bindToolbarButtons = function () {
        _this.toolbarPanel.searchButton.onClick = _this.showSearch;
        _this.toolbarPanel.faveButton.onClick = _this.showFave;
        _this.toolbarPanel.refreshButton.onClick = _this.refresh;
    };
    _this.resetView = function () {
        _this.loader.hide();
        _this.searchPanel.clear();
        _this.listPanel.clear();
        _this.searchPanel.hide();
    };
    _this.showSearch = function() {
        _this.dataSource.resetLastOperation();
        _this.resetView();
        _this.searchPanel.show();
        _this.searchPanel.bindOnSearchEvent(function (searchQuery) { _this.loadStationEta(searchQuery);});

        _this.listPanel.setTitle('חיפוש');
        _this.listPanel.resetListSize();
        _this.listPanel.bindListClickAction(function () {});
    };
    _this.showFave = function() {
        _this.loader.show();
        _this.dataSource.fetchFaveStations();
        _this.listPanel.bindListClickAction(function (row) { _this.loadStationEta(row.station.id)});
    };
    _this.refresh = function() {
        _this.loader.show();
        _this.dataSource.invokeLastOperation();
    };
    _this.loadStationEta = function(stationId)
    {
        _this.loader.show();
        _this.dataSource.fetchLineETA(stationId);
        _this.listPanel.bindListClickAction(function () {});
    };
    _this.construct();
}