// ========== CONFIGURAÇÃO ==========
const API_URL = 'http://localhost/projeto-jogo-php/api/index.php';
let token = localStorage.getItem('token');
let userId = null;

// ========== ELEMENTOS DO JOGO ==========
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');

// Configurações do jogo
let gameRunning = false;
let score = 0;
let player = { 
    x: 50, 
    y: canvas.height - 50 - 30, 
    velocity: 0, 
    gravity: 0.8, 
    jumpPower: -12,
    width: 30,
    height: 30
};
let obstacles = [];
let frame = 0;
let gameLoop = null;
const GROUND_Y = canvas.height - 50;

// ========== FUNÇÃO PARA TESTAR CONEXÃO ==========
async function testarConexao() {
    console.log('Testando API em:', API_URL);
    
    try {
        const response = await fetch('http://localhost/projeto-jogo-php/api/teste.php');
        const data = await response.json();
        console.log('✅ Conexão OK:', data);
        return true;
    } catch(error) {
        console.error('❌ Erro de conexão:', error);
        return false;
    }
}

// ========== FUNÇÕES DE AUTENTICAÇÃO ==========
async function login() {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    
    if(!email || !password) {
        alert('Preencha email e senha');
        return;
    }
    
    showLoading(true);
    
    try {
        const response = await fetch(`${API_URL}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if(data.token) {
            token = data.token;
            localStorage.setItem('token', token);
            await carregarDadosUsuario();
            document.getElementById('authPanel').style.display = 'none';
            document.getElementById('gamePanel').style.display = 'block';
            iniciarJogo();
        } else {
            alert('Erro no login: ' + (data.error || 'Credenciais inválidas'));
        }
    } catch(error) {
        console.error('Erro detalhado:', error);
        alert('Erro ao conectar: ' + error.message);
    } finally {
        showLoading(false);
    }
}

async function register() {
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;
    
    if(!email || !password) {
        alert('Preencha email e senha');
        return;
    }
    
    if(password.length < 4) {
        alert('Senha deve ter no mínimo 4 caracteres');
        return;
    }
    
    showLoading(true);
    
    try {
        const response = await fetch(`${API_URL}/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if(data.success) {
            alert('Cadastro realizado com sucesso! Faça login.');
            showLogin();
        } else {
            alert('Erro no cadastro: ' + (data.error || 'Tente outro email'));
        }
    } catch(error) {
        console.error('Erro:', error);
        alert('Erro ao conectar: ' + error.message);
    } finally {
        showLoading(false);
    }
}

async function carregarDadosUsuario() {
    if(!token) return;
    
    try {
        const response = await fetch(`${API_URL}/user`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        const data = await response.json();
        
        if(data.id) {
            userId = data.id;
            document.getElementById('userEmail').innerText = data.email;
            document.getElementById('pontos').innerText = data.pontos || 0;
            document.getElementById('saldoReais').innerText = parseFloat(data.saldo_reais || 0).toFixed(2);
            score = data.pontos || 0;
        } else if(data.error) {
            console.error('Erro:', data.error);
            logout();
        }
    } catch(error) {
        console.error('Erro ao carregar usuário:', error);
    }
}

function logout() {
    localStorage.removeItem('token');
    token = null;
    userId = null;
    document.getElementById('authPanel').style.display = 'block';
    document.getElementById('gamePanel').style.display = 'none';
    if(gameLoop) clearInterval(gameLoop);
}

function showRegister() {
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('registerForm').style.display = 'block';
}

function showLogin() {
    document.getElementById('loginForm').style.display = 'block';
    document.getElementById('registerForm').style.display = 'none';
}

function showLoading(show) {
    let loading = document.getElementById('loading');
    if(!loading) {
        loading = document.createElement('div');
        loading.id = 'loading';
        loading.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:20px;border-radius:10px;z-index:2000;display:none';
        loading.innerHTML = 'Processando...';
        document.body.appendChild(loading);
    }
    loading.style.display = show ? 'flex' : 'none';
}

// ========== FUNÇÕES DO JOGO ==========
function iniciarJogo() {
    if(gameLoop) clearInterval(gameLoop);
    
    gameRunning = true;
    score = 0;
    player.y = GROUND_Y - player.height;
    player.velocity = 0;
    obstacles = [];
    frame = 0;
    
    document.getElementById('pontos').innerText = score;
    
    gameLoop = setInterval(updateGame, 20);
    desenhar();
}

function pular() {
    if(!gameRunning) return;
    if(player.y >= GROUND_Y - player.height - 5) {
        player.velocity = player.jumpPower;
    }
}

function updateGame() {
    if(!gameRunning) return;
    
    player.velocity += player.gravity;
    player.y += player.velocity;
    
    if(player.y >= GROUND_Y - player.height) {
        player.y = GROUND_Y - player.height;
        player.velocity = 0;
    }
    
    if(player.y < 0) {
        player.y = 0;
        if(player.velocity < 0) player.velocity = 0;
    }
    
    frame++;
    if(frame > 70 && Math.random() < 0.02) {
        obstacles.push({ 
            x: canvas.width, 
            y: GROUND_Y - 30,
            width: 20,
            height: 30
        });
        frame = 0;
    }
    
    for(let i = 0; i < obstacles.length; i++) {
        obstacles[i].x -= 5;
        
        if(obstacles[i].x + obstacles[i].width < 0) {
            obstacles.splice(i, 1);
            score++;
            document.getElementById('pontos').innerText = score;
            i--;
        }
    }
    
    for(let obstacle of obstacles) {
        if(player.x < obstacle.x + obstacle.width &&
           player.x + player.width > obstacle.x &&
           player.y < obstacle.y + obstacle.height &&
           player.y + player.height > obstacle.y) {
            gameOver();
            break;
        }
    }
    
    desenhar();
}

function desenhar() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    ctx.fillStyle = '#8B4513';
    ctx.fillRect(0, GROUND_Y, canvas.width, 5);
    
    ctx.fillStyle = '#2ecc71';
    for(let i = 0; i < 10; i++) {
        ctx.fillRect(i * 80, GROUND_Y - 8, 5, 8);
    }
    
    ctx.fillStyle = '#2d2d2d';
    ctx.fillRect(player.x, player.y, player.width, player.height);
    
    ctx.fillStyle = 'white';
    ctx.fillRect(player.x + 20, player.y + 8, 5, 5);
    ctx.fillStyle = 'black';
    ctx.fillRect(player.x + 21, player.y + 9, 3, 3);
    
    ctx.fillStyle = '#ff0000';
    ctx.fillRect(player.x + 25, player.y + 18, 5, 3);
    
    ctx.fillStyle = '#27ae60';
    for(let obstacle of obstacles) {
        ctx.fillRect(obstacle.x, obstacle.y, obstacle.width, obstacle.height);
        ctx.fillRect(obstacle.x + 5, obstacle.y - 8, 3, 8);
        ctx.fillRect(obstacle.x + 12, obstacle.y - 5, 3, 5);
    }
    
    ctx.fillStyle = 'black';
    ctx.font = 'bold 20px Arial';
    ctx.fillText(`Score: ${score}`, 10, 30);
}

function gameOver() {
    if(!gameRunning) return;
    gameRunning = false;
    clearInterval(gameLoop);
    alert(`🎮 Fim de jogo!\n📊 Você fez ${score} pontos!\n💡 Converta seus pontos em dinheiro!`);
    salvarPontuacao();
}

async function salvarPontuacao() {
    if(!token) return;
    
    try {
        await fetch(`${API_URL}/update-score`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ pontos: score })
        });
        await carregarDadosUsuario();
    } catch(error) {
        console.error('Erro ao salvar pontuação:', error);
    }
}

// ========== SISTEMA DE PONTOS E SAQUE ==========
async function converterPontos() {
    if(!token) {
        alert('Faça login primeiro');
        return;
    }
    
    const pontosAtuais = parseInt(document.getElementById('pontos').innerText);
    
    if(pontosAtuais < 100) {
        alert(`Você precisa de pelo menos 100 pontos para converter.\nVocê tem ${pontosAtuais} pontos.`);
        return;
    }
    
    showLoading(true);
    
    try {
        const response = await fetch(`${API_URL}/convert-points`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ pontos: pontosAtuais })
        });
        
        const data = await response.json();
        
        if(data.success) {
            alert(`✅ Convertido! R$ ${data.valor_reais.toFixed(2)} adicionado ao saldo.`);
            await carregarDadosUsuario();
        } else {
            alert('❌ Erro na conversão: ' + (data.error || 'Tente novamente'));
        }
    } catch(error) {
        console.error('Erro:', error);
        alert('Erro ao converter pontos: ' + error.message);
    } finally {
        showLoading(false);
    }
}

function mostrarSaque() {
    if(!token) {
        alert('Faça login primeiro');
        return;
    }
    
    document.getElementById('withdrawModal').style.display = 'flex';
    document.getElementById('qrcodeSection').style.display = 'none';
    document.getElementById('withdrawSection').style.display = 'block';
}

function fecharModal() {
    document.getElementById('withdrawModal').style.display = 'none';
    document.getElementById('qrcodeSection').style.display = 'none';
    document.getElementById('pixKey').value = '';
    document.getElementById('withdrawAmount').value = '';
}

async function salvarChavePix() {
    const pixKey = document.getElementById('pixKey').value;
    const pixType = document.getElementById('pixType').value;
    
    if(!pixKey) {
        alert('Digite sua chave Pix');
        return;
    }
    
    showLoading(true);
    
    try {
        const response = await fetch(`${API_URL}/save-pix`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ pix_key: pixKey, pix_type: pixType })
        });
        
        const data = await response.json();
        
        if(data.success) {
            alert('✅ Chave Pix salva com sucesso!');
            document.getElementById('pixKey').value = '';
            fecharModal();
        } else {
            alert('❌ Erro ao salvar: ' + (data.error || 'Tente novamente'));
        }
    } catch(error) {
        console.error('Erro:', error);
        alert('Erro ao salvar chave Pix: ' + error.message);
    } finally {
        showLoading(false);
    }
}

async function solicitarSaque() {
    const amount = parseFloat(document.getElementById('withdrawAmount').value);
    
    if(isNaN(amount) || amount < 5) {
        alert('Valor mínimo para saque é R$ 5,00');
        return;
    }
    
    const saldoAtual = parseFloat(document.getElementById('saldoReais').innerText);
    if(amount > saldoAtual) {
        alert(`Saldo insuficiente! Você tem R$ ${saldoAtual.toFixed(2)}`);
        return;
    }
    
    showLoading(true);
    
    try {
        console.log('Enviando requisição para:', `${API_URL}/withdraw`);
        console.log('Valor:', amount);
        
        const response = await fetch(`${API_URL}/withdraw`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ valor: amount })
        });
        
        const data = await response.json();
        console.log('Resposta:', data);
        
        if(data.success) {
            if(data.qr_code_base64) {
                document.getElementById('qrcodeImage').src = 'data:image/png;base64,' + data.qr_code_base64;
            }
            document.getElementById('copyPasteCode').innerHTML = `📋 Código Pix:<br><small style="word-break:break-all">${data.copy_paste}</small>`;
            document.getElementById('qrcodeSection').style.display = 'block';
            document.getElementById('withdrawSection').style.display = 'none';
            
            alert(`✅ Saque de R$ ${amount.toFixed(2)} solicitado! Escaneie o QR Code.`);
            await carregarDadosUsuario();
        } else {
            alert('❌ ' + (data.error || 'Erro no saque'));
        }
    } catch(error) {
        console.error('Erro detalhado:', error);
        alert('Erro ao solicitar saque: ' + error.message);
    } finally {
        showLoading(false);
    }
}

// ========== CONTROLES ==========
document.addEventListener('keydown', (e) => {
    if(e.code === 'Space') {
        e.preventDefault();
        if(gameRunning) pular();
    }
});

if(canvas) {
    canvas.addEventListener('click', () => {
        if(gameRunning) pular();
    });
}

// ========== INICIALIZAÇÃO ==========
window.addEventListener('load', async function() {
    console.log('Página carregada, testando conexão...');
    const conectado = await testarConexao();
    console.log('Conexão testada, resultado:', conectado);
    
    if (conectado && token) {
        console.log('Token encontrado, carregando dados...');
        carregarDadosUsuario().then(() => {
            document.getElementById('authPanel').style.display = 'none';
            document.getElementById('gamePanel').style.display = 'block';
            iniciarJogo();
        }).catch((err) => {
            console.error('Erro ao carregar dados:', err);
            localStorage.removeItem('token');
        });
    } else if(conectado) {
        console.log('Sem token, mostrando login');
    }
});