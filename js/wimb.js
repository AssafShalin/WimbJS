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
var lineTemplate = '<div class="line_container" id="{0}"><div class="station_operator" style="background-image: url("img/operators/{1}.png");"></div><div class="station_name">{2}</div><div class="station_location">{3}</div>{4}</div>';
//id,name,desc,id
var stationTemplate = '<div class="station_container" id="{0}"><div class="station_name">{1}</div><div class="station_location">{2}</div><div class="station_number">מספר תחנה {3}</div></div>';
//</editor-fold>

//<editor-fold desc="Model-Objects">
function Line()
{
    this.id = 0;
    this.number = 0;
    this.description = '';
    this.operator = 0;
    this.eta = 0;
}
function Station()
{
    this.id = 0;
    this.name = '';
    this.alias = '';
    this.desc = '';
}
//</editor-fold>

//<editor-fold desc="UI-Controls">
function ListBox(id)
{
    this.id = id;
    /**
     *
     * @type {ListBoxRow}
     */
    this.rows = [];
    this.clear = function () {
        $(this.id).html('');
        this.rows = [];
    };
    this.render = function() {
        this.clear();
        for(i=0;i<this.rows.length;i++) {
            var content = '<tr><td>' + rows[i].render() + '</td></tr>';
            $(this.id).append(content);
        }
    };
    this.add = function (rowAdapter) {
        this.rows.push(rowAdapter);
    }
}
function ListBoxRow()
{
   this.render = function () { return ''; };
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
    this.id = '#search-panel';
    this.searchTextBox = new TextBox('search-text');
    this.searchButton = new Button('search-button');
    this.show = function  ()    { $(this.id).show(); };
    this.hide = function  ()    { $(this.id).hide(); };
    this.clear = function ()    { this.searchTextBox.clear(); };
}
function ListPanel()
{
    this.listBox = new ListBox('#station-list');
    this.listTitle = new Label('#station-title');
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
    self.render = function () {
        stationTemplate.format( this.station.id,
                                this.station.name,
                                this.station.desc,
                                this.station.id);
    };

    return self;
}
function LineListBoxRowAdapter(line)
{
    this.line = line;
    var self = new ListBoxRow();
    self.render = function () {
        lineTemplate.format(this.line.id,
                            this.line.operator,
                            this.line.name,
                            this.line.desc,
                            this.line.eta);
    };

    return self;
}
var ListBoxAdapterFactory = {
    getAdapter: function (object) {
        var type = Object.prototype.toString(object);
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

function WimbData() {
    var _this = this;
    this.fave = [];
    this.lastOperation = 0;
    this.get = function(url, callback) {
        console.log('requesting data from ' + url);
        $.get(url, function (data){
            console.log('data received.');
            callback(data);
        });
    };
    this.onOperationFinish = function() {};
    this.fetchFaveStations = function (force) {
          if(this.fave.length == 0 || force)
          {
              this.get('ajax/stations.php',function (stations) {
                  _this.fave = stations;
                  _this.lastOperation = _this.fetchFaveStations;
                  _this.onOperationFinish(_this.fave);
              });
          }
    };
    this.fetchLineETA = function(station) {
        this.get('ajax/info.php?stop_code=' + station.id, function (data) {
           _this.lastOperation = _this.fetchLineETA;
           _this.onOperationFinish(data);
        });
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
        for(i=0;i<data.length;i++) {
            _this.listPanel.listBox.add(ListBoxAdapterFactory.getAdapter(data[i]));
        }
        _this.loader.hide();
    };
    _this.bindToolbarButtons = function () {
        _this.toolbarPanel.searchButton.onClick = _this.showSearch;
        _this.toolbarPanel.faveButton.onClick = _this.showFave;
        _this.toolbarPanel.refreshButton = _this.refresh;
    };
    _this.resetView = function () {
        _this.loader.hide();
        _this.searchPanel.clear();
        _this.listPanel.clear();
        _this.searchPanel.hide();
    };
    _this.showSearch = function() {
        _this.resetView();
        _this.listPanel.setTitle('חיפוש');
        _this.searchPanel.show();
        _this.listPanel.resetListSize();
    };
    _this.showFave = function() {
        _this.resetView();
        _this.loader.show();
        _this.listPanel.setTitle('התחנות שלי');
        _this.listPanel.resetListSize();
        _this.dataSource.fetchFaveStations();
    };
    _this.refresh = function() {
        _this.resetView();
        _this.dataSource.lastOperation();
    };
    _this.construct();
}

$(document).ready(function () {
    var wimb = new WimbUI();
});
