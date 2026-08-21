/* Base de Procedimentos Técnicos — JavaScript mínimo, sem dependências.
   Tudo o que aqui está é um melhoramento: a aplicação funciona sem JavaScript. */
(function () {
  'use strict';

  /* ------------------------------------------------------------------
     1. Confirmação antes de acções destrutivas (apagar, arquivar)
     ------------------------------------------------------------------ */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.dataset && form.dataset.confirm) {
      if (!window.confirm(form.dataset.confirm)) {
        e.preventDefault();
      }
    }
  });

  /* ------------------------------------------------------------------
     2. Editor de passos: adicionar, remover, reordenar (botões, teclado e arrastar)
     ------------------------------------------------------------------ */
  var editor = document.querySelector('[data-passos]');
  if (editor) {
    var lista = editor.querySelector('.passos');
    var modelo = editor.querySelector('template');
    var btnAdicionar = editor.querySelector('[data-adicionar-passo]');
    var aviso = editor.querySelector('[data-aviso-passos]');

    function itens() { return Array.prototype.slice.call(lista.querySelectorAll('.passo')); }

    function renumerar() {
      itens().forEach(function (li, i, arr) {
        li.querySelector('.passo__num').textContent = (i + 1) + '.';
        var ta = li.querySelector('textarea');
        ta.setAttribute('aria-label', 'Passo ' + (i + 1));
        li.querySelector('[data-subir]').disabled = (i === 0);
        li.querySelector('[data-descer]').disabled = (i === arr.length - 1);
        li.querySelector('[data-remover]').disabled = (arr.length === 1);
      });
    }

    function anunciar(msg) {
      if (aviso) { aviso.textContent = ''; setTimeout(function () { aviso.textContent = msg; }, 50); }
    }

    function novoPasso(texto) {
      var frag = modelo.content.cloneNode(true);
      var li = frag.querySelector('.passo');
      li.querySelector('textarea').value = texto || '';
      lista.appendChild(li);
      renumerar();
      return li;
    }

    btnAdicionar.addEventListener('click', function () {
      var li = novoPasso('');
      li.querySelector('textarea').focus();
      anunciar('Passo ' + itens().length + ' adicionado.');
    });

    lista.addEventListener('click', function (e) {
      var btn = e.target.closest('button');
      if (!btn) return;
      var li = btn.closest('.passo');
      var idx = itens().indexOf(li);

      if (btn.hasAttribute('data-remover')) {
        if (itens().length <= 1) return;
        var foco = li.nextElementSibling || li.previousElementSibling;
        li.remove();
        renumerar();
        anunciar('Passo ' + (idx + 1) + ' removido.');
        if (foco) foco.querySelector('textarea').focus();
      } else if (btn.hasAttribute('data-subir') && li.previousElementSibling) {
        lista.insertBefore(li, li.previousElementSibling);
        renumerar();
        btn.focus();
        anunciar('Passo movido para a posição ' + idx + '.');
      } else if (btn.hasAttribute('data-descer') && li.nextElementSibling) {
        lista.insertBefore(li.nextElementSibling, li);
        renumerar();
        btn.focus();
        anunciar('Passo movido para a posição ' + (idx + 2) + '.');
      }
    });

    // Atalhos de teclado dentro do texto: Alt+↑ / Alt+↓ move o passo
    lista.addEventListener('keydown', function (e) {
      if (!e.altKey || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) return;
      var li = e.target.closest('.passo');
      if (!li) return;
      e.preventDefault();
      var btn = li.querySelector(e.key === 'ArrowUp' ? '[data-subir]' : '[data-descer]');
      if (!btn.disabled) { btn.click(); li.querySelector('textarea').focus(); }
    });

    // Arrastar e largar (rato / toque em browsers que suportam)
    var arrastado = null;
    lista.addEventListener('dragstart', function (e) {
      var li = e.target.closest('.passo');
      if (!li) return;
      arrastado = li;
      li.classList.add('arrastando');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', '');
    });
    lista.addEventListener('dragover', function (e) {
      if (!arrastado) return;
      e.preventDefault();
      var alvo = e.target.closest('.passo');
      itens().forEach(function (x) { x.classList.remove('sobre'); });
      if (alvo && alvo !== arrastado) alvo.classList.add('sobre');
    });
    lista.addEventListener('drop', function (e) {
      if (!arrastado) return;
      e.preventDefault();
      var alvo = e.target.closest('.passo');
      if (alvo && alvo !== arrastado) {
        var antes = itens().indexOf(alvo) < itens().indexOf(arrastado);
        lista.insertBefore(arrastado, antes ? alvo : alvo.nextElementSibling);
        renumerar();
      }
    });
    lista.addEventListener('dragend', function () {
      itens().forEach(function (x) { x.classList.remove('sobre', 'arrastando'); });
      arrastado = null;
    });

    // Só as pegas iniciam o arrasto; o textarea tem de continuar seleccionável
    lista.addEventListener('mousedown', function (e) {
      var li = e.target.closest('.passo');
      if (!li) return;
      li.setAttribute('draggable', e.target.closest('.passo__pega') ? 'true' : 'false');
    });

    renumerar();
  }

  /* ------------------------------------------------------------------
     3. Mostrar / ocultar a palavra-passe
     ------------------------------------------------------------------ */
  Array.prototype.forEach.call(document.querySelectorAll('[data-ver-password]'), function (btn) {
    var campo = document.getElementById(btn.getAttribute('data-ver-password'));
    if (!campo) return;

    var olhoMostrar = btn.querySelector('[data-olho-mostrar]');
    var olhoOcultar = btn.querySelector('[data-olho-ocultar]');

    btn.addEventListener('click', function () {
      var aMostrar = campo.type === 'password';
      campo.type = aMostrar ? 'text' : 'password';

      if (olhoMostrar) olhoMostrar.hidden = aMostrar;
      if (olhoOcultar) olhoOcultar.hidden = !aMostrar;

      var etiqueta = (aMostrar ? 'Ocultar' : 'Mostrar') + ' palavra-passe';
      btn.setAttribute('aria-pressed', aMostrar ? 'true' : 'false');
      btn.setAttribute('aria-label', etiqueta);
      btn.setAttribute('title', etiqueta);
      campo.focus();
    });
  });

  /* ------------------------------------------------------------------
     4. Consulta: filtro instantâneo + expandir/recolher todos
     ------------------------------------------------------------------ */
  var consulta = document.querySelector('[data-consulta]');
  if (consulta) {
    var form = consulta.querySelector('form.filtros');
    var inputQ = form.querySelector('[name="q"]');
    var selCat = form.querySelector('[name="categoria"]');
    var cards = Array.prototype.slice.call(consulta.querySelectorAll('.proc'));
    var contagem = consulta.querySelector('[data-contagem]');
    var semResultados = consulta.querySelector('[data-sem-resultados]');
    var linkImprimir = consulta.querySelector('[data-imprimir-todos]');
    var temporizador;

    function normalizar(s) {
      return (s || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    function aplicar() {
      var q = normalizar(inputQ.value.trim());
      var cat = selCat.value;
      var visiveis = 0;

      cards.forEach(function (card) {
        var ok = true;
        if (cat && card.dataset.categoria !== cat) ok = false;
        if (ok && q && normalizar(card.dataset.texto).indexOf(q) === -1) ok = false;
        card.hidden = !ok;
        if (ok) visiveis++;
      });

      if (contagem) {
        contagem.textContent = visiveis === 0 ? 'Nenhum procedimento corresponde aos filtros.'
          : visiveis === 1 ? '1 procedimento' : visiveis + ' procedimentos';
      }
      if (semResultados) semResultados.hidden = (visiveis !== 0 || cards.length === 0);

      // Actualiza o endereço e o link de impressão para reflectir os filtros
      var params = new URLSearchParams();
      if (inputQ.value.trim()) params.set('q', inputQ.value.trim());
      if (cat) params.set('categoria', cat);
      var qs = params.toString();
      if (window.history.replaceState) {
        window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
      }
      if (linkImprimir) {
        linkImprimir.href = linkImprimir.dataset.base + (qs ? '?' + qs : '');
      }
    }

    inputQ.addEventListener('input', function () {
      clearTimeout(temporizador);
      temporizador = setTimeout(aplicar, 120);
    });
    selCat.addEventListener('change', aplicar);
    form.addEventListener('submit', function (e) { e.preventDefault(); aplicar(); });

    var btnLimpar = form.querySelector('[data-limpar]');
    if (btnLimpar) {
      btnLimpar.addEventListener('click', function (e) {
        e.preventDefault();
        inputQ.value = ''; selCat.value = '';
        aplicar();
        inputQ.focus();
      });
    }

    var btnExpandir = consulta.querySelector('[data-expandir]');
    var btnRecolher = consulta.querySelector('[data-recolher]');
    if (btnExpandir) btnExpandir.addEventListener('click', function () {
      cards.forEach(function (c) { if (!c.hidden) c.open = true; });
    });
    if (btnRecolher) btnRecolher.addEventListener('click', function () {
      cards.forEach(function (c) { c.open = false; });
    });

    // Abre automaticamente o procedimento indicado no endereço (#proc-12)
    if (window.location.hash) {
      var alvo = document.querySelector(window.location.hash);
      if (alvo && alvo.classList.contains('proc')) { alvo.open = true; alvo.scrollIntoView(); }
    }
  }

  /* ------------------------------------------------------------------
     5. Impressão automática quando a página de impressão é aberta com ?auto=1
     ------------------------------------------------------------------ */
  if (document.body.dataset.autoImprimir === '1') {
    window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 200); });
  }

  /* ------------------------------------------------------------------
     Barra lateral no telemóvel: abrir, fechar e devolver o foco.
     Em ecrãs largos a barra está sempre visível e isto não faz nada.
     ------------------------------------------------------------------ */
  var lateral = document.querySelector('[data-lateral]');
  var veu = document.querySelector('.lateral__veu');

  function abrirLateral() {
    if (!lateral) return;
    lateral.classList.add('aberta');
    if (veu) veu.hidden = false;
    var primeiro = lateral.querySelector('a, button');
    if (primeiro) primeiro.focus();
  }

  function fecharLateral() {
    if (!lateral) return;
    lateral.classList.remove('aberta');
    if (veu) veu.hidden = true;
    var abrir = document.querySelector('[data-abrir-lateral]');
    if (abrir) abrir.focus();
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-abrir-lateral]'), function (b) {
    b.addEventListener('click', abrirLateral);
  });
  Array.prototype.forEach.call(document.querySelectorAll('[data-fechar-lateral]'), function (b) {
    b.addEventListener('click', fecharLateral);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && lateral && lateral.classList.contains('aberta')) fecharLateral();
  });

})();
