var w3 = {};
w3.displayObject = function (id, data) {
  var htmlObj = document.getElementById(id);
  htmlTemplate = init_template(id, htmlObj);
  html = htmlTemplate.cloneNode(true);
  html = w3_replace_curly(html, "element");
  htmlObj.parentNode.replaceChild(html, htmlObj);
  function init_template(id, obj) {
    var template;
    template = obj.cloneNode(true);
    return template;
  }
  function w3_replace_curly(elmnt, typ, repeatX, x) {
    rowClone = elmnt.cloneNode(true);
    pos1 = 0;
    while (pos1 > -1) {
      originalHTML = (typ == "attribute") ? rowClone.value : rowClone.innerHTML;
      pos1 = originalHTML.indexOf("{{", pos1);
      if (pos1 === -1) {break;}
      pos2 = originalHTML.indexOf("}}", pos1 + 1);
      lookFor = originalHTML.substring(pos1 + 2, pos2);
      lookForARR = lookFor.split("||");
      for (i = 0; i < lookForARR.length; i += 1) {
        lookForARR[i] = lookForARR[i].replace(/^\s+|\s+$/gm, '');
        if (data) {
          lookForARR = lookForARR + '';
          lookForARR = lookForARR.split('.');
          value = data[lookForARR[i]];
          value = value + '';
          value = value.split(',');
          if(lookForARR[1] != undefined) {
            value = value[lookForARR[1]];
          }else{
            value = value[0];
          }
        }
        if (value != undefined) {break;}
      }
      if (value != undefined) {
        r = "{{" + lookFor + "}}";
        w3_replace_html(rowClone, r, value);
      }
      pos1 = pos1 + 1;
    }
    return rowClone;
  }
  function w3_replace_html(a, r, result) {
    var a;
    a.innerHTML = a.innerHTML.replace(r, result);
  }
};
