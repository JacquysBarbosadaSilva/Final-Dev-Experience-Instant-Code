# Final-Dev-Experience-Instant-Code

# Módulo Sensor NRF24L01 (Nó Único) - Monitoramento e Controle

Este código é projetado para um nó sensor Arduino que utiliza o sensor DHT11 para medir temperatura e umidade, e o módulo NRF24L01 para comunicar esses dados a uma estação central (via Python/NRF24L01) a cada 5 segundos.

O módulo também gerencia o controle local de um Servo Motor (simulando uma esteira) e um Buzzer (simulando um alarme), reagindo a comandos remotos ou a condições críticas de temperatura.

## ⚙️ HARDWARE NECESSÁRIO

| Componente | Função | Pino do Arduino |
| :--- | :--- | :--- |
| **Arduino Uno/Nano** | Microcontrolador | - |
| **NRF24L01** | Comunicação Sem Fio | CE: D9, CSN: D10 |
| **DHT11** | Sensor de Temperatura/Umidade | D6 |
| **Servo Motor** | Simula a Esteira | D8 |
| **Buzzer Ativo** | Alarme Sonoro | D7 |
| **Capacitor 10µF** | Estabilização do NRF24L01 | **CRÍTICO** (entre VCC e GND do NRF) |

⚠️ **ATENÇÃO:** O módulo NRF24L01 deve ser alimentado com **3.3V**. Nunca o ligue diretamente na saída 5V do Arduino sem um conversor de nível.

## 📚 BIBLIOTECAS NECESSÁRIAS

Instale as seguintes bibliotecas através do Gerenciador de Bibliotecas do Arduino IDE:

1.  **RF24** (by TMRh20)
2.  **DHT sensor library** (by Adafruit)
3.  **Adafruit Unified Sensor** (Dependência da DHT Library)
4.  **Servo** (Geralmente pré-instalada)
5.  **SPI** (Geralmente pré-instalada)

## 📡 CONFIGURAÇÃO DE COMUNICAÇÃO

As seguintes configurações devem ser **IDÊNTICAS** no código Python/Receptor:

| Parâmetro | Valor no Código |
| :--- | :--- |
| **Pipe de Escrita (para Central)** | `"CENTRAL"` |
| **Pipe de Leitura (para Comandos)** | `"NODE1"` |
| **Canal** | `108` |
| **Taxa de Dados** | `RF24_250KBPS` |

## 🧠 LÓGICA DE FUNCIONAMENTO

### 1. Transmissão de Dados
- O módulo lê os dados do DHT11 e transmite para a Central a cada **5 segundos**.
- Além dos dados, envia o estado atual do Motor e do Alarme.

### 2. Controle Automático Local (Segurança)
- Se `Temperatura > 30.0 °C`:
    - O `estadoMotor` é definido como **DESLIGADO** (para a esteira).
    - O `estadoAlarme` é definido como **LIGADO** (aciona o buzzer).
- Se `Temperatura <= 30.0 °C`:
    - O `estadoMotor` é definido como **LIGADO** (retoma o movimento).
    - O `estadoAlarme` é definido como **DESLIGADO** (desliga o buzzer).

### 3. Recebimento de Comandos Remotos
O módulo está sempre em modo de escuta para receber comandos da Central. Os comandos remotos (**LMOTOR/DMOTOR/ALIGAR/ADESLIGAR**) **substituem** temporariamente a lógica de controle automático (exceto se a temperatura crítica persistir).

---

# Estação Central Python - Recepção de Dados e Envio de Comandos NRF24L01

Este script Python atua como a estação central de monitoramento. Ele lê os dados do Módulo Sensor via porta Serial (USB), armazena esses dados em CSV, SQLite e Excel, e exibe um gráfico em tempo real. Além disso, ele é responsável por enviar comandos de controle remotos de volta para o Arduino.

⚠️ **IMPORTANTE:** Este script espera que os dados de Temperatura (`Temp:`) e Umidade (`Umid:`) venham em linhas separadas da Serial do Arduino. O código Arduino fornecido foi ajustado para garantir isso.

## 📦 PRÉ-REQUISITOS E INSTALAÇÃO

Este projeto requer Python 3.x e as seguintes bibliotecas. Instale-as usando `pip`:

```bash
pip install pyserial pandas matplotlib sqlite3
