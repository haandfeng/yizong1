

var Validator = function(name)
{
  this.formName = name;
  this.errMsg = new Array();
 
  this.required = function(controlId, msg)
  {
    var obj = document.forms[this.formName].elements[controlId];
    if (typeof(obj) == "undefined" || Utils.trim(obj.value) == "")
    {
      this.addErrorMsg(msg);
    }
  }
  ;
 
  this.isEmail = function(controlId, msg, required)
  {
    var obj = document.forms[this.formName].elements[controlId];
    obj.value = Utils.trim(obj.value);

    if ( ! required && obj.value == '')
    {
      return;
    }

    if ( ! Utils.isEmail(obj.value))
    {
      this.addErrorMsg(msg);
    }
  }
 
  this.eqaul = function(fstControl, sndControl, msg)
  {
    var fstObj = document.forms[this.formName].elements[fstControl];
    var sndObj = document.forms[this.formName].elements[sndControl];

    if (fstObj != null && sndObj != null)
    {
      if (fstObj.value == '' || fstObj.value != sndObj.value)
      {
        this.addErrorMsg(msg);
      }
    }
  }
 
  this.gt = function(fstControl, sndControl, msg)
  {
    var fstObj = document.forms[this.formName].elements[fstControl];
    var sndObj = document.forms[this.formName].elements[sndControl];

    if (fstObj != null && sndObj != null) {
      if (Utils.isNumber(fstObj.value) && Utils.isNumber(sndObj.value)) {
        var v1 = parseFloat(fstObj.value) + 0;
        var v2 = parseFloat(sndObj.value) + 0;
      } else {
        var v1 = fstObj.value;
        var v2 = sndObj.value;
      }

      if (v1 <= v2) this.addErrorMsg(msg);
    }
  }
 
  this.isNumber = function(controlId, msg, required)
  {
    var obj = document.forms[this.formName].elements[controlId];
    obj.value = Utils.trim(obj.value);

    if (obj.value == '' && ! required)
    {
      return;
    }
    else
    {
      if ( ! Utils.isNumber(obj.value))
      {
        this.addErrorMsg(msg);
      }
    }
  }
 
  this.isInt = function(controlId, msg, required)
  {

    if (document.forms[this.formName].elements[controlId])
    {
      var obj = document.forms[this.formName].elements[controlId];
    }
    else
    {
      return;    
    }

    obj.value = Utils.trim(obj.value);

    if (obj.value == '' && ! required)
    {
      return;
    }
    else
    {
      if ( ! Utils.isInt(obj.value)) this.addErrorMsg(msg);
    }
  }
 
  this.isNullOption = function(controlId, msg)
  {
    var obj = document.forms[this.formName].elements[controlId];

    obj.value = Utils.trim(obj.value);

    if (obj.value > '0' )
    {
      return;
    }
    else
    {
      this.addErrorMsg(msg);
    }
  }
 
  this.isTime = function(controlId, msg, required)
  {
    var obj = document.forms[this.formName].elements[controlId];
    obj.value = Utils.trim(obj.value);

    if (obj.value == '' && ! required)
    {
      return;
    }
    else
    {
      if ( ! Utils.isTime(obj.value)) this.addErrorMsg(msg);
    }
  }
   
  this.islt = function(controlIdStart, controlIdEnd, msg)
  {
    var start = document.forms[this.formName].elements[controlIdStart];
    var end = document.forms[this.formName].elements[controlIdEnd];
    start.value = Utils.trim(start.value);
    end.value = Utils.trim(end.value);

    if(start.value <= end.value)
    {
      return;
    }
    else
    {
      this.addErrorMsg(msg);
    }
  }
 
  this.requiredCheckbox = function(chk, msg)
  {
    var obj = document.forms[this.formName].elements[controlId];
    var checked = false;

    for (var i = 0; i < objects.length; i ++ )
    {
      if (objects[i].type.toLowerCase() != "checkbox") continue;
      if (objects[i].checked)
      {
        checked = true;
        break;
      }
    }

    if ( ! checked) this.addErrorMsg(msg);
  }

  this.passed = function()
  {
    if (this.errMsg.length > 0)
    {
      var msg = "";
      for (i = 0; i < this.errMsg.length; i ++ )
      {
        msg += "- " + this.errMsg[i] + "\n";
      }

      alert(msg);
      return false;
    }
    else
    {
      return true;
    }
  }
 
  this.addErrorMsg = function(str)
  {
    this.errMsg.push(str);
  }
}
 
function showNotice(objId)
{
  var obj = document.getElementById(objId);

  if (obj)
  {
    if (obj.style.display != "block")
    {
      obj.style.display = "block";
    }
    else
    {
      obj.style.display = "none";
    }
  }
}
 
function addItem(src, dst)
{
  for (var x = 0; x < src.length; x ++ )
  {
    var opt = src.options[x];
    if (opt.selected && opt.value != '')
    {
      var newOpt = opt.cloneNode(true);
      newOpt.className = '';
      newOpt.text = newOpt.innerHTML.replace(/^\s+|\s+$|&nbsp;/g, '');
      dst.appendChild(newOpt);
    }
  }

  src.selectedIndex = -1;
}
 
function delItem(ItemList)
{
  for (var x = ItemList.length - 1; x >= 0; x -- )
  {
    var opt = ItemList.options[x];
    if (opt.selected)
    {
      ItemList.options[x] = null;
    }
  }
}
 
function joinItem(ItemList)
{
  var OptionList = new Array();
  for (var i = 0; i < ItemList.length; i ++ )
  {
    OptionList[OptionList.length] = ItemList.options[i].text + "|" + ItemList.options[i].value;
  }
  return OptionList.join(",");
}
