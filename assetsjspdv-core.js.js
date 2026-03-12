/**
 * PDV Tanque Digital - Core JavaScript
 * Versão: 2.0.0 | Responsável: Akiles Simião
 */

const PDV = {
  state: {
    itens: [],
    total: 0,
    empresa_id: null,
    licenca: null,
    modo_demo: false
  },

  init() {
    this.carregarConfig();
    this.bindEvents();
    this.verificarLicenca();
    console.log('✅ PDV inicializado');
  },

  carregarConfig() {
    const config = document.getElementById('pdv-config');
    if (config) {
      this.state.empresa_id = config.dataset.empresaId;
      this.state.modo_demo = config.dataset.demo === 'true';
    }
  },

  bindEvents() {
    // Busca de produtos
    const busca = document.getElementById('busca');
    if (busca) {
      busca.addEventListener('input', this.debounce(this.buscarProduto.bind(this), 300));
      document.addEventListener('click', (e) => {
        if (!e.target.closest('.busca-produto')) {
          document.getElementById('resultados-busca').style.display = 'none';
        }
      });
    }

    // Finalizar venda
    const btnFinalizar = document.getElementById('btn-finalizar');
    if (btnFinalizar) {
      btnFinalizar.addEventListener('click', this.finalizarVenda.bind(this));
    }

    // Modal de senha admin
    document.querySelectorAll('[data-requer-admin]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.modalSenha(btn.dataset.acao);
      });
    });
  },

  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  },

  async buscarProduto(e) {
    const termo = e.target.value.trim();
    if (termo.length < 2) {
      document.getElementById('resultados-busca').style.display = 'none';
      return;
    }

    try {
      const response = await fetch(`/api/produtos/buscar?q=${encodeURIComponent(termo)}&empresa_id=${this.state.empresa_id}`);
      const produtos = await response.json();
      
      const container = document.getElementById('resultados-busca');
      container.innerHTML = produtos.map(p => `
        <div class="resultado-item" data-id="${p.id}" data-preco="${p.preco_venda}">
          <span>${p.nome} ${p.codigo_barras ? `(${p.codigo_barras})` : ''}</span>
          <strong>R$ ${parseFloat(p.preco_venda).toFixed(2)}</strong>
        </div>
      `).join('');
      
      container.style.display = 'block';
      
      // Clique para adicionar ao carrinho
      container.querySelectorAll('.resultado-item').forEach(item => {
        item.addEventListener('click', () => this.adicionarItem(item.dataset));
      });
    } catch (err) {
      console.error('Erro na busca:', err);
    }
  },

  adicionarItem({ id, preco }) {
    const existente = this.state.itens.find(i => i.produto_id == id);
    if (existente) {
      existente.quantidade += 1;
    } else {
      this.state.itens.push({
        produto_id: id,
        quantidade: 1,
        preco_unitario: parseFloat(preco)
      });
    }
    this.atualizarCarrinho();
    document.getElementById('resultados-busca').style.display = 'none';
    document.getElementById('busca').value = '';
  },

  atualizarCarrinho() {
    const tbody = document.querySelector('#tabela-itens tbody');
    if (!tbody) return;

    this.state.total = this.state.itens.reduce((sum, item) => {
      const subtotal = item.quantidade * item.preco_unitario;
      return sum + subtotal;
    }, 0);

    tbody.innerHTML = this.state.itens.map((item, idx) => `
      <tr>
        <td>Item #${item.produto_id}</td>
        <td>
          <input type="number" value="${item.quantidade}" min="1" 
                 data-idx="${idx}" class="qtd-input" style="width:60px">
        </td>
        <td>R$ ${item.preco_unitario.toFixed(2)}</td>
        <td>R$ ${(item.quantidade * item.preco_unitario).toFixed(2)}</td>
        <td><button class="btn-remove" data-idx="${idx}">✕</button></td>
      </tr>
    `).join('');

    document.getElementById('subtotal').textContent = `R$ ${this.state.total.toFixed(2)}`;
    document.getElementById('total').textContent = `R$ ${this.state.total.toFixed(2)}`;

    // Re-bind events
    tbody.querySelectorAll('.btn-remove').forEach(btn => {
      btn.addEventListener('click', (e) => {
        this.state.itens.splice(e.target.dataset.idx, 1);
        this.atualizarCarrinho();
      });
    });

    tbody.querySelectorAll('.qtd-input').forEach(input => {
      input.addEventListener('change', (e) => {
        const idx = e.target.dataset.idx;
        const novaQtd = parseInt(e.target.value) || 1;
        this.state.itens[idx].quantidade = Math.max(1, novaQtd);
        this.atualizarCarrinho();
      });
    });
  },

  async finalizarVenda() {
    if (this.state.itens.length === 0) {
      alert('Adicione pelo menos um item ao carrinho');
      return;
    }

    const formaPagamento = document.querySelector('.opcoes-pagamento button.selected')?.dataset.pag;
    if (!formaPagamento) {
      alert('Selecione uma forma de pagamento');
      return;
    }

    // Verificar restrições DEMO
    if (this.state.modo_demo && formaPagamento === 'pix') {
      alert('PIX disponível apenas na versão PRO. Ative em gerador.php');
      return;
    }

    const btn = document.getElementById('btn-finalizar');
    btn.disabled = true;
    btn.textContent = 'Processando...';

    try {
      const response = await fetch('/api/vendas/criar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          empresa_id: this.state.empresa_id,
          itens: this.state.itens,
          forma_pagamento: formaPagamento,
          tipo_cupom: this.state.licenca?.permissoes?.fiscal ? 'fiscal' : 'nao_fiscal'
        })
      });

      const resultado = await response.json();
      
      if (resultado.sucesso) {
        // Gerar cupom
        this.imprimirCupom(resultado.venda);
        
        // Limpar carrinho
        this.state.itens = [];
        this.atualizarCarrinho();
        
        // Mostrar confirmação
        this.mostrarConfirmacao(resultado.venda);
        
        // Se for PIX, mostrar QR Code
        if (formaPagamento === 'pix' && resultado.pix_qrcode) {
          this.mostrarPixQR(resultado.pix_qrcode, resultado.pix_copia_cola);
        }
      } else {
        throw new Error(resultado.erro || 'Erro ao finalizar venda');
      }
    } catch (err) {
      console.error(err);
      alert('❌ ' + err.message);
    } finally {
      btn.disabled = false;
      btn.textContent = '✅ Finalizar Venda';
    }
  },

  imprimirCupom(venda) {
    const cupom = window.open('', '_blank', 'width=400,height=600');
    cupom.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Cupom #${venda.numero_cupom}</title>
        <style>
          body { font-family: 'Courier New', monospace; font-size: 12px; padding: 10px; }
          .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
          .item { display: flex; justify-content: space-between; margin: 5px 0; }
          .total { border-top: 2px dashed #000; padding-top: 10px; margin-top: 10px; font-weight: bold; }
          .footer { text-align: center; margin-top: 20px; font-size: 10px; }
          @media print { body { font-size: 10px; } }
        </style>
      </head>
      <body onload="window.print()">
        <div class="header">
          <strong>TANQUE DIGITAL</strong><br>
          Quiosque 40, Lagoa Azul - Natal/RN<br>
          Cupom #${venda.numero_cupom}<br>
          ${new Date(venda.data_venda).toLocaleString('pt-BR')}
        </div>
        ${venda.itens.map(i => `
          <div class="item">
            <span>${i.quantidade}x ${i.produto_nome}</span>
            <span>R$ ${(i.quantidade * i.preco_unitario).toFixed(2)}</span>
          </div>
        `).join('')}
        <div class="total">
          <div class="item">
            <span>TOTAL</span>
            <span>R$ ${parseFloat(venda.total).toFixed(2)}</span>
          </div>
          <div class="item">
            <span>Pagamento:</span>
            <span>${venda.forma_pagamento.toUpperCase()}</span>
          </div>
        </div>
        <div class="footer">
          ${this.state.licenca?.permissoes?.fiscal 
            ? 'DOCUMENTO AUXILIAR DA NFC-e<br>Consulte pela chave de acesso' 
            : 'CUPOM NÃO FISCAL - SEM VALOR TRIBUTÁRIO'}
          <br><br>
          ${this.state.modo_demo ? '*** VERSÃO DEMO ***' : ''}
        </div>
      </body>
      </html>
    `);
    cupom.document.close();
  },

  async verificarLicenca() {
    try {
      const token = localStorage.getItem('pdv_token');
      if (!token) {
        window.location.href = '/install/ativacao.php';
        return;
      }

      const response = await fetch('/api/validar_licenca', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token, empresa_id: this.state.empresa_id })
      });

      const resultado = await response.json();
      
      if (resultado.valido) {
        this.state.licenca = resultado.dados;
        // Ativar funcionalidades conforme plano
        if (resultado.dados.permissoes?.pix) {
          document.querySelector('[data-pag="pix"]')?.removeAttribute('disabled');
        }
        if (resultado.dados.permissoes?.fiscal) {
          document.getElementById('btn-fiscal')?.classList.remove('hidden');
        }
      } else {
        localStorage.removeItem('pdv_token');
        window.location.href = '/install/ativacao.php';
      }
    } catch (err) {
      console.error('Erro validação licença:', err);
    }
  },

  modalSenha(acao) {
    const modal = document.createElement('div');
    modal.className = 'modal active';
    modal.innerHTML = `
      <div class="modal-content">
        <div class="modal-header">
          <h3>🔐 Senha Administrativa</h3>
          <button class="modal-close">&times;</button>
        </div>
        <p>Para ${acao}, informe a senha de administrador:</p>
        <input type="password" id="admin-pin" placeholder="••••" style="width:100%;padding:10px;margin:10px 0;font-size:1.2rem;letter-spacing:5px">
        <button id="btn-confirmar-senha" class="btn-primary">Confirmar</button>
      </div>
    `;
    document.body.appendChild(modal);

    modal.querySelector('.modal-close').onclick = () => modal.remove();
    modal.querySelector('#btn-confirmar-senha').onclick = async () => {
      const pin = document.getElementById('admin-pin').value;
      try {
        const response = await fetch('/api/admin/verificar_senha', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ pin, empresa_id: this.state.empresa_id })
        });
        const result = await response.json();
        if (result.valido) {
          modal.remove();
          // Executar ação protegida
          this.executarAcaoProtegida(acao);
        } else {
          alert('Senha incorreta');
        }
      } catch (err) {
        alert('Erro de conexão');
      }
    };
  },

  async executarAcaoProtegida(acao) {
    // Implementar conforme ação: cancelar_venda, reemitir_cupom, etc.
    console.log(`Executando ação protegida: ${acao}`);
  },

  mostrarConfirmacao(venda) {
    const modal = document.createElement('div');
    modal.className = 'modal active';
    modal.innerHTML = `
      <div class="modal-content text-center">
        <h2 class="text-success">✅ Venda Finalizada!</h2>
        <p style="margin:1rem 0">Cupom #${venda.numero_cupom}</p>
        <p><strong>Total: R$ ${parseFloat(venda.total).toFixed(2)}</strong></p>
        <div style="margin-top:1.5rem;display:flex;gap:0.5rem;justify-content:center">
          <button onclick="this.closest('.modal').remove()" class="btn-primary" style="flex:1">Fechar</button>
          <button onclick="PDV.compartilharWhatsApp('${venda.numero_cupom}')" style="flex:1;padding:1rem;background:#25D366;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600">
            📱 Enviar WhatsApp
          </button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  },

  compartilharWhatsApp(numeroCupom) {
    const texto = `*Tanque Digital* 🛒\n\nCupom: #${numeroCupom}\nData: ${new Date().toLocaleString('pt-BR')}\n\nObrigado pela preferência! 💙\n\n📍 Quiosque 40, Lagoa Azul - Natal/RN\n🔗 tanquedigital.com.br`;
    const url = `https://wa.me/?text=${encodeURIComponent(texto)}`;
    window.open(url, '_blank');
  },

  mostrarPixQR(qrcodeBase64, copiaCola) {
    const modal = document.createElement('div');
    modal.className = 'modal active';
    modal.innerHTML = `
      <div class="modal-content text-center">
        <h3>💳 Pagamento via PIX</h3>
        <img src="data:image/png;base64,${qrcodeBase64}" alt="QR Code PIX" style="max-width:200px;margin:1rem auto;display:block">
        <p style="font-size:0.9rem;color:#666;margin:0.5rem 0">Escaneie com o app do seu banco</p>
        <details style="text-align:left;margin:1rem 0;background:#f8f9fa;padding:1rem;border-radius:8px">
          <summary style="cursor:pointer;font-weight:500">📋 Ou use o código "copia e cola"</summary>
          <textarea readonly style="width:100%;margin-top:0.5rem;padding:0.5rem;font-family:monospace;font-size:0.8rem" rows="3">${copiaCola}</textarea>
          <button onclick="navigator.clipboard.writeText(this.previousElementSibling.value);alert('Copiado!')" style="margin-top:0.5rem;padding:0.3rem 1rem;background:var(--primary);color:white;border:none;border-radius:4px;cursor:pointer">Copiar</button>
        </details>
        <button onclick="this.closest('.modal').remove();PDV.consultarPixStatus()" class="btn-primary" style="margin-top:1rem">
          🔍 Já paguei? Verificar
        </button>
      </div>
    `;
    document.body.appendChild(modal);
  }
};

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => PDV.init());