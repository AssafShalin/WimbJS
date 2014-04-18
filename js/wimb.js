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
    this.id = id;
    this.onClick = onClick || function(button) {};
    //bind on click
    $(id).click(function() { this.onClick(this);});
}

function Label(id)
{
    this.id = id;
    this.getValue = function()      {   return $(this.id).html();    };
    this.setValue = function(value) {   $(this.id).html(value);      };
    this.clear    = function()      {   $(this.id).html('');         };
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
    }
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
    this.fave = [];
    this.lastOperation = 0;
    this.onOperationFinish = function() {};


    this.fetchFaveStations = function (force) {
          if(this.fave.length == 0 || force)
          {
              $.get('ajax/stations.php',function (stations) {
                  this.fave = stations;
                  this.lastOperation = this.fetchFaveStations;
                  this.onOperationFinish(this.fave);
              });
          }
    };
    this.fetchLineETA = function(station) {
        $.get('ajax/info.php?stop_code=' + station.id, function (data) {
           this.lastOperation = this.fetchLineETA;
           this.onOperationFinish(data);
        });
    };
}

function WimbUI()
{
    this.dataSource = new WimbData();
    this.toolbarPanel = new ToolbarPanel();
    this.searchPanel = new SearchPanel();
    this.listPanel = new ListPanel();
    this.loader = new LoaderPanel();
    this.construct = function() {
        this.bindToolbarButtons();
        this.dataSource.onOperationFinish = this.dataSourceOperationFinish;

    };
    this.dataSourceOperationFinish = function (data) {
        for(i=0;i<data.length;i++) {
            this.listPanel.listBox.add(ListBoxAdapterFactory.getAdapter(data[i]));
        }
        this.loader.hide();
    };
    this.bindToolbarButtons = function () {
        this.toolbarPanel.searchButton.onClick = this.showSearch;
        this.toolbarPanel.faveButton.onClick = this.showFave;
        this.toolbarPanel.refreshButton = this.refresh;
    };
    this.resetView = function () {
        this.loader.hide();
        this.searchPanel.clear();
        this.listPanel.clear();
        this.searchPanel.hide();
    };
    this.showSearch = function() {
        this.resetView();
        this.searchPanel.show();
    };
    this.showFave = function() {
        this.resetView();
        this.loader.show();
        this.dataSource.fetchFaveStations();
    };
    this.refresh = function() {
        this.resetView();
        this.dataSource.lastOperation();
    };
    this.construct();
}