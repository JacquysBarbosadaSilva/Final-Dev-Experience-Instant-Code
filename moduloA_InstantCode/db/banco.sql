CREATE DATABASE IF NOT EXISTS youtan_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE youtan_monitor;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel_permissao ENUM('admin', 'colaborador') NOT NULL DEFAULT 'colaborador',
    setor VARCHAR(100),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE categorias_ativos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE ativos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    id_categoria INT NOT NULL,
    valor DECIMAL(15, 2),
    data_aquisicao DATE,
    numero_serie VARCHAR(100) UNIQUE,
    fabricante VARCHAR(100),
    modelo VARCHAR(100),
    status ENUM('operacional', 'manutencao', 'descartado') NOT NULL DEFAULT 'operacional',
    localizacao VARCHAR(255) NOT NULL,
    id_usuario_responsavel INT,
    data_garantia DATE,
    observacoes TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria) REFERENCES categorias_ativos(id),
    FOREIGN KEY (id_usuario_responsavel) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE manutencoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_ativo INT NOT NULL,
    tipo ENUM('preventiva', 'corretiva', 'preditiva') NOT NULL,
    data_manutencao DATE NOT NULL,
    data_agendamento DATE,
    custo DECIMAL(15, 2),
    tecnico_responsavel VARCHAR(100),
    descricao TEXT,
    status ENUM('agendada', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'agendada',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ativo) REFERENCES ativos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('manutencao', 'vencimento', 'critico') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    id_ativo INT,
    id_manutencao INT,
    severidade ENUM('baixa', 'media', 'alta', 'critica') DEFAULT 'media',
    status ENUM('pendente', 'visto', 'resolvido') DEFAULT 'pendente',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_resolucao TIMESTAMP NULL,
    FOREIGN KEY (id_ativo) REFERENCES ativos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_manutencao) REFERENCES manutencoes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE transferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_ativo INT NOT NULL,
    id_usuario_origem INT,
    id_usuario_destino INT NOT NULL,
    data_transferencia TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,
    FOREIGN KEY (id_ativo) REFERENCES ativos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario_origem) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (id_usuario_destino) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO categorias_ativos (nome, descricao) VALUES 
('Hardware', 'Equipamentos de informática e periféricos'),
('Software', 'Licenças e aplicativos'),
('Móveis', 'Mobiliário corporativo'),
('Veículos', 'Frota corporativa'),
('Equipamentos', 'Maquinários e equipamentos especiais');

INSERT INTO usuarios (nome, email, senha, nivel_permissao, setor) VALUES 
('Administrador', 'admin@youtan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'TI'),
('João Silva', 'joao@youtan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'colaborador', 'Vendas');