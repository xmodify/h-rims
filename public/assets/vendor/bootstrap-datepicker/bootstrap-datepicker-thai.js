/**
 * Implement Thai-year handling inherit core datepicker and default bootstrap-datepicker backend.
 */

;(function($) {
  var dates   = $.fn.datepicker.dates
    , DPGlobal= $.fn.datepicker.DPGlobal
    , thai    = { 
                  adj     : 543
                , code    : 'th'
                , bound   : 2400  // full year value that detect as thai year 
                , shbound : 40  // short year value that detect as thai year 
                , shwrap  : 70  // short year value that wrap to previous century
                , shbase  : 2000  // default base for short year 20xx
                }
                
  function dspThaiYear(language) {
    if (!language) return false;
    return language.search('-'+thai.code)>=0
  }
  
  function smartThai(language){
    if (!language) return false;
    return language.search(thai.code)>=0
  }
  
  function smartFullYear(v,language){
    if (smartThai(language) && v>=thai.bound) 
      v -= thai.adj // thaiyear 24xx -
    
    if (dspThaiYear(language) && v < thai.bound - thai.adj) 
      v -= thai.adj
    
    return v;
  }
  
  function smartShortYear(v,language) {
    if (v<100){
      if (v>=thai.shwrap) 
        v -= 100; // 1970 - 1999
        
      if (smartThai(language) && v>=thai.shbound) 
        v -= (thai.adj%100) // thaiyear [2540..2569] -> [1997..2026]

      v += thai.shbase;
    }
    return v;
  }
  
  function smartYear(v,language) {
    return smartFullYear(smartShortYear(v,language),language)
  }
  
  function UTCDate() {
    return new Date(Date.UTC.apply(Date, arguments))
  }

  // inherit default backend
  
  if (DPGlobal.name && DPGlobal.name.search(/.th$/)>=0)
    return
    
  var  _basebackend_ = $.extend({},DPGlobal)
  
  $.extend(DPGlobal,{
      name:       (_basebackend_.name || '') + '.th'
    , parseDate:  
        function(date, format, language) {
          if (date == '' || date == null || date == undefined) {
            return _basebackend_.parseDate.call(this, date, format, language);
          }
          var parsed = _basebackend_.parseDate.call(this, date, format, language);
          if (parsed && smartThai(language)) {
            var year = parsed.getUTCFullYear();
            if (year >= thai.bound) {
              parsed.setUTCFullYear(year - thai.adj);
            }
          }
          return parsed;
        }
    , formatDate: 
        function(date, format, language){
          if (dspThaiYear(language)){
            var dateCopy = new Date(date.getTime());
            dateCopy.setUTCFullYear(date.getUTCFullYear() + thai.adj);
            return _basebackend_.formatDate.call(this, dateCopy, format, language);
          }
          return _basebackend_.formatDate.call(this, date, format, language);
        }
    })

  // inherit core datepicker
  var DatePicker = $.fn.datepicker.Constructor
  
  if (!DatePicker.prototype.fillThai){
    var _basemethod_ = $.extend({},DatePicker.prototype)
    
    $.extend(DatePicker.prototype,{
        fillThai: function(){
            var d         = new Date(this.viewDate)
              , year      = d.getUTCFullYear()
              , month     = d.getUTCMonth()
              , elem      = this.picker.find('.datepicker-days th:eq(1)')
              
            elem
              .text(elem.text()
              .replace(''+year,''+(year+thai.adj)))

            this.picker
              .find('.datepicker-months')
              .find('th:eq(1)')
              .text(''+(year+thai.adj))
              
            year = parseInt((year+thai.adj)/10, 10) * 10
            
            this.picker
              .find('.datepicker-years')
              .find('th:eq(1)')
              .text(year + '-' + (year + 9))
              .end()
              .find('td')
              .find('span.year')
              .each( 
                function() {
                  $(this)
                    .text(Number($(this).text()) + thai.adj)
                })
          }
      , fill: function(){
            _basemethod_.fill.call(this)
            
            var lang = this.language || (this.o && this.o.language) || 'en';
            if (dspThaiYear(lang))
              this.fillThai()
          }
      , clickThai: function(e){
            var target  = $(e.target).closest('span')
            
            if (target.length === 1 && target.is('.year'))
              target.text(Number(target.text()) - thai.adj)
          }
      , click: function(e){
            var lang = this.language || (this.o && this.o.language) || 'en';
            if (dspThaiYear(lang))
              this.clickThai(e)
              
            _basemethod_.click.call(this,e)
          }
      , keydown: function(e){
            // allow arrow-down to show picker
            if (this.picker.is(':not(:visible)')
            && e.keyCode == 40 // arrow-down
            && $(e.target).is('[autocomplete="off"]')) {
                  this.show()
                  return;
            }
            _basemethod_.keydown.call(this,e)
          }
      , hide: function(e){
            // fix redundant hide in orginal code
            if (this.picker.is(':visible'))
              _basemethod_.hide.call(this,e)
            //else console.log('redundant hide')
          }
      
    })
  }
}(jQuery));
