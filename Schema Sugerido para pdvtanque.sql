-- Tabela de empresas/clientes
CREATE TABLE empresas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    cnpj VARCHAR(18) UNIQUE,
    email VARCHAR(100),
    telefone VARCHAR(20),
    endereco TEXT,
    status ENUM('ativo', 'inativo', 'demo') DEFAULT 'demo',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de licenças/ativações
CREATE TABLE licencas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT,
    token_unico VARCHAR(64) UNIQUE NOT NULL,
    dias_validade INT DEFAULT 30,
    data_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_expiracao DATETIME,
    status ENUM('ativa', 'expirada', 'cancelada') DEFAULT 'ativa',
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

-- Tabela de produtos
CREATE TABLE produtos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT,
    nome VARCHAR(100) NOT NULL,
    codigo_barras VARCHAR(50),
    preco DECIMAL(10,2) NOT NULL,
    estoque INT DEFAULT 0,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

-- Tabela de vendas
CREATE TABLE vendas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT,
    cliente_id INT,
    total DECIMAL(10,2),
    forma_pagamento ENUM('pix', 'cartao', 'dinheiro'),
    tipo_cupom ENUM('fiscal', 'nao_fiscal'),
    data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);