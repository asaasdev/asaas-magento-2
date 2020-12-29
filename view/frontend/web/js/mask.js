function ResetCampos() {
  var textFields = document.getElementsByTagName("input");
  for (var i = 0; i < textFields.length; i++) {
    if (textFields[i].type == "text") {
      textFields[i].style.backgroundColor = "";
      textFields[i].style.borderColor = "";
    }
  }
}

function mascara(m, t, e, c) {
  var cursor = t.selectionStart;
  var texto = t.value;
  texto = texto.replace(/\D/g, '');
  var l = texto.length;
  var lm = m.length;
  if (window.event) {
    id = e.keyCode;
  } else if (e.which) {
    id = e.which;
  }
  cursorfixo = false;
  if (cursor < l) cursorfixo = true;
  var livre = false;
  if (id == 16 || id == 19 || (id >= 33 && id <= 40)) livre = true;
  ii = 0;
  mm = 0;
  if (!livre) {
    if (id != 8) {
      t.value = "";
      j = 0;
      for (i = 0; i < lm; i++) {
        if (m.substr(i, 1) == "#") {
          t.value += texto.substr(j, 1);
          j++;
        } else if (m.substr(i, 1) != "#") {
          t.value += m.substr(i, 1);
        }
        if (id != 8 && !cursorfixo) cursor++;
        if ((j) == l + 1) break;

      }
    }
  }
  if (cursorfixo && !livre) cursor--;
  t.setSelectionRange(cursor, cursor);
}

function limit(element)
{
    var max_chars = 4;

    if(element.value.length > max_chars) {
        element.value = element.value.substr(0, max_chars);
    }
}

function ccmask(input) {
    var oldValue,
    oldCursor,
    regex = new RegExp(/^\d{0,16}$/g),
    mask = function (value) {
      var output = [];
      for (var i = 0; i < value.length; i++) {
        if (i !== 0 && i % 4 === 0) {
          output.push(" "); // add the separator
        }
        output.push(value[i]);
      }
      return output.join("");
    },
    unmask = function (value) {
      var output = value.replace(new RegExp(/[^\d]/, 'g'), ''); // Remove every non-digit character
      return output;
    },
    checkSeparator = function (position, interval) {
      return Math.floor(position / (interval + 1));
    },
    keydownHandler = function (e) {
      var el = e.target;

      oldValue = el.value;
      oldCursor = el.selectionEnd;
    },
    inputHandler = function (e) {
      var el = e.target,
        newCursorPosition,
        newValue = unmask(el.value)
        ;

      if (newValue.match(regex)) {
        newValue = mask(newValue);

        newCursorPosition = oldCursor - checkSeparator(oldCursor, 4) + checkSeparator(oldCursor + (newValue.length - oldValue.length), 4) + (unmask(newValue).length - unmask(oldValue).length);

        if (newValue !== "") {
          el.value = newValue;
        } else {
          el.value = "";
        }
      } else {
        el.value = oldValue;
        newCursorPosition = oldCursor;
      }
      el.setSelectionRange(newCursorPosition, newCursorPosition);
    }
    ;

  input.addEventListener('keydown', keydownHandler);
  input.addEventListener('input', inputHandler);
}

function mascaraMutuario(o, f) {
  v_obj = o
  v_fun = f
  setTimeout('execmascara()', 1)
}

function execmascara() {
  v_obj.value = v_fun(v_obj.value)
}

function cpfCnpj(v) {

  //Remove tudo o que não é dígito
  v = v.replace(/\D/g, "")
  if (v.length <= 11) { //CPF

    //Coloca um ponto entre o terceiro e o quarto dígitos
    v = v.replace(/(\d{3})(\d)/, "$1.$2")

    //Coloca um ponto entre o terceiro e o quarto dígitos
    //de novo (para o segundo bloco de números)
    v = v.replace(/(\d{3})(\d)/, "$1.$2")

    //Coloca um hífen entre o terceiro e o quarto dígitos
    v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2")

  } else { //CNPJ

    //Coloca ponto entre o segundo e o terceiro dígitos
    v = v.replace(/^(\d{2})(\d)/, "$1.$2")

    //Coloca ponto entre o quinto e o sexto dígitos
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3")

    //Coloca uma barra entre o oitavo e o nono dígitos
    v = v.replace(/\.(\d{3})(\d)/, ".$1/$2")

    //Coloca um hífen depois do bloco de quatro dígitos
    v = v.replace(/(\d{4})(\d)/, "$1-$2")

  }

  return v
}