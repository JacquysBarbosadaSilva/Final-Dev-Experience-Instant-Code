// ============================================================================
// RECEPTOR CENTRAL (NÓ ÚNICO) - PARA ENVIAR COMANDOS E RECEBER DADOS
// ============================================================================

#include <SPI.h>
#include <nRF24L01.h>
#include <RF24.h>

// =================== CONFIGURAÇÕES DE PINOS =====================
#define CE_PIN 7
#define CSN_PIN 8
// SPI: MOSI=11, MISO=12, SCK=13 (fixos)

// Endereços de Comunicação (IDÊNTICOS ao Sensor)
const byte EnderecoLeitura[6] = "CENTRAL"; // Central lê do endereço CENTRAL
const byte EnderecoEscrita[6] = "NODE1";   // Central escreve para o Nó 1

// ========================= ESTRUTURAS ===========================
// Dados recebidos do Sensor
struct DadosSensor {
  char idSensor;
  float temperatura;
  float umidade;
  bool motorLigado;
  bool alarmeAtivo;
};

// Comandos enviados para o Sensor
struct ComandoCentral {
  char idDestino; 
  bool ligarMotor;
  bool ligarAlarme;
};

// ======================== OBJETOS E ESTADO ======================
RF24 radio(CE_PIN, CSN_PIN);
const long TIMEOUT_CONEXAO = 15000; // 15 segundos sem dados = aviso
unsigned long ultimaRecepcao = 0;

// ============================ SETUP =============================
void setup() {
  Serial.begin(9600);
  Serial.println("================ CENTRAL INICIADA ================");
  
  if (!radio.begin()) {
    Serial.println("ERRO: Falha NRF24L01! Verifique fiação e 3.3V.");
    while (1);
  }
  
  // Configurações NRF24L01
  radio.setPALevel(RF24_PA_MAX);
  radio.setDataRate(RF24_250KBPS);
  radio.setChannel(108);
  radio.setAutoAck(true);
  radio.enableAckPayload();
  
  // Configura pipe de LEITURA (para receber do Nó)
  radio.openReadingPipe(1, EnderecoLeitura); 
  
  radio.startListening(); // Modo escuta padrão
  Serial.println("NRF24L01 OK. Aguardando dados do sensor...");
  
  // Instruções de Comando
  Serial.println("\n--- COMANDOS DE CONTROLE (Monitor Serial) ---");
  Serial.println("Use: LMOTOR, DMOTOR, ALIGAR, ADESLIGAR");
  Serial.println("----------------------------------------------\n");
}

// ============================= LOOP ===============================
void loop() {
  // 1. Coleta de Dados do Sensor
  coletarDados();
  
  // 2. Processamento de Comandos Seriais
  processarComandoSerial();
  
  // 3. Verificação de Timeout
  verificarTimeout();
}

// ======================= FUNÇÕES AUXILIARES ======================

void coletarDados() {
  // Está sempre no modo escuta
  if (radio.available()) {
    DadosSensor dadosRecebidos;
    radio.read(&dadosRecebidos, sizeof(dadosRecebidos));
    
    ultimaRecepcao = millis();
    
    // Imprime os dados
    Serial.println("------------------------------------");
    Serial.println("<< DADOS RECEBIDOS");
    Serial.print("  Temperatura: "); Serial.print(dadosRecebidos.temperatura, 1); Serial.println(" °C");
    Serial.print("  Umidade:     "); Serial.print(dadosRecebidos.umidade, 1); Serial.println(" %");
    Serial.print("  Motor:       "); Serial.println(dadosRecebidos.motorLigado ? "LIGADO" : "PARADO");
    Serial.print("  Alarme:      "); Serial.println(dadosRecebidos.alarmeAtivo ? "ATIVO" : "DESLIGADO");
    Serial.println("------------------------------------");
  }
}

void processarComandoSerial() {
  if (Serial.available()) {
    String acao = Serial.readStringUntil('\n');
    acao.trim();
    acao.toUpperCase();
    
    ComandoCentral comando;
    comando.idDestino = '1'; // Destino fixo para o nó único
    
    bool comandoValido = false;

    // Determina a ação
    if (acao.equals("LMOTOR")) {
      comando.ligarMotor = true;
      comando.ligarAlarme = false; // Não muda o alarme
      comandoValido = true;
    } else if (acao.equals("DMOTOR")) {
      comando.ligarMotor = false;
      comando.ligarAlarme = false; // Não muda o alarme
      comandoValido = true;
    } else if (acao.equals("ALIGAR")) {
      comando.ligarMotor = false; // Não muda o motor
      comando.ligarAlarme = true;
      comandoValido = true;
    } else if (acao.equals("ADESLIGAR")) {
      comando.ligarMotor = false; // Não muda o motor
      comando.ligarAlarme = false;
      comandoValido = true;
    } else {
      Serial.println("Comando inválido. Use LMOTOR, DMOTOR, ALIGAR, ADESLIGAR.");
      return;
    }
    
    if (comandoValido) {
        enviarComando(comando);
    }
  }
}

void enviarComando(ComandoCentral comando) {
  radio.stopListening(); // MODO TRANSMISSÃO
  radio.openWritingPipe(EnderecoEscrita);
  
  if (radio.write(&comando, sizeof(comando))) {
    Serial.print(">> Comando ENVIADO (");
    if (comando.ligarMotor) Serial.print("LMOTOR");
    else if (!comando.ligarMotor) Serial.print("DMOTOR");
    
    if (comando.ligarAlarme) Serial.print(" / ALIGAR");
    else if (!comando.ligarAlarme) Serial.print(" / ADESLIGAR");
    
    Serial.println(")");
  } else {
    Serial.println("!! FALHA ao enviar comando.");
  }
  
  radio.startListening(); // Volta para modo recepção
}

void verificarTimeout() {
  unsigned long tempoAtual = millis();
  
  if (ultimaRecepcao != 0 && (tempoAtual - ultimaRecepcao > TIMEOUT_CONEXAO)) {
    Serial.println("⚠️ AVISO: Sensor não responde há mais de 15 segundos!");
    ultimaRecepcao = tempoAtual; // Reseta o aviso para não poluir o serial
  }
}