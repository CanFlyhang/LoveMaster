// Single Page Application Logic

const UI = {
    scenarioSelect: document.getElementById('scenarioSelect'),
    creditCount: document.getElementById('creditCount'),
    roleTitle: document.getElementById('roleTitle'),
    npcName: document.getElementById('npcName'),
    topicContent: document.getElementById('topicContent'),
    messageInput: document.getElementById('messageInput'),
    submitBtn: document.getElementById('submitBtn'),
    
    // Modals
    resultModal: document.getElementById('resultModal'),
    modalContent: document.getElementById('modalContent'),
    scoreValue: document.getElementById('scoreValue'),
    analysisContent: document.getElementById('analysisContent'),
    bestReplyContent: document.getElementById('bestReplyContent'),
    
    noCreditModal: document.getElementById('noCreditModal'),
    shareBtn: document.getElementById('shareBtn'),

    // 用户中心
    userCenterModal: document.getElementById('userCenterModal'),
    ucPanel: document.getElementById('ucPanel'),
    ucOpenId: document.getElementById('ucOpenId'),
    ucCredits: document.getElementById('ucCredits'),
    ucHistoryList: document.getElementById('ucHistoryList'),
    ucRechargeBtn: document.getElementById('ucRechargeBtn')
};

let currentUser = null;
let currentSession = null;
let isProcessing = false;

// Config
const SCENARIOS = {
    'dating_male': { title: '女朋友', npc: '女朋友', role: '恋爱 - 哄女友' }
};

document.addEventListener('DOMContentLoaded', async () => {
    await initUser();
    
    // 默认加载唯一场景
    switchScenario('dating_male');
});

async function initUser() {
    // 1. Check local storage
    const storedUser = localStorage.getItem('spa_user');
    if (storedUser) {
        currentUser = JSON.parse(storedUser);
        // Refresh credits
        await refreshUserInfo();
    } else {
        // 2. Guest Login
        try {
            // PHP Endpoint
            const res = await api.post('/guest_login.php', {});
            currentUser = res;
            localStorage.setItem('spa_user', JSON.stringify(currentUser));
            updateCreditUI(res.credits);
        } catch (err) {
            console.error(err);
            alert('初始化失败，请刷新重试');
        }
    }
}

async function refreshUserInfo() {
    try {
        // PHP Endpoint
        const res = await api.get(`/user_me.php?user_id=${currentUser.user_id}`);
        // 同步 openid、昵称、体力到本地
        currentUser.openid = res.openid;
        currentUser.nickname = res.nickname;
        currentUser.credits = res.credits;
        localStorage.setItem('spa_user', JSON.stringify(currentUser));
        updateCreditUI(res.credits);
    } catch (err) {
        console.error('Failed to refresh user info');
    }
}

function updateCreditUI(credits) {
    UI.creditCount.textContent = credits;
    if (credits <= 0) {
        UI.creditCount.parentElement.classList.add('bg-gray-200', 'text-gray-500');
        UI.creditCount.parentElement.classList.remove('bg-red-50', 'text-red-500');
    } else {
        UI.creditCount.parentElement.classList.remove('bg-gray-200', 'text-gray-500');
        UI.creditCount.parentElement.classList.add('bg-red-50', 'text-red-500');
    }
}

async function switchScenario(type) {
    const conf = SCENARIOS[type];
    UI.roleTitle.textContent = conf.title;
    UI.npcName.textContent = conf.npc;
    
    // Start new session for this scenario
    await startSession(type);
}

const LOADING_SPINNER = `
<div class="flex flex-col items-center justify-center py-8 space-y-3">
    <svg class="animate-spin h-8 w-8 text-pink-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <span class="text-gray-400 text-sm animate-pulse">女友正在组织语言...</span>
</div>
`;

async function startSession(type) {
    if (isProcessing) return;
    isProcessing = true;
    UI.topicContent.innerHTML = LOADING_SPINNER;
    
    try {
        const res = await api.post('/game_start.php', {
            user_id: currentUser.user_id,
            type: type
        });
        currentSession = res;
        UI.topicContent.textContent = res.topic;
        UI.messageInput.value = '';
    } catch (err) {
        UI.topicContent.textContent = '加载失败，请重试';
    } finally {
        isProcessing = false;
    }
}

async function refreshTopic() {
    if (isProcessing || !currentSession) return;
    
    isProcessing = true;
    UI.topicContent.innerHTML = LOADING_SPINNER;
    
    try {
        const res = await api.post('/game_refresh.php', {
            session_id: currentSession.session_id
        });
        UI.topicContent.textContent = res.topic;
        UI.messageInput.value = '';
    } catch (err) {
        UI.topicContent.textContent = '刷新失败';
    } finally {
        isProcessing = false;
    }
}

async function submitReply() {
    if (isProcessing) return;
    
    // Check credits locally first
    if (currentUser.credits <= 0) {
        showNoCreditModal();
        return;
    }

    const text = UI.messageInput.value.trim();
    if (!text) {
        alert("请输入回复内容");
        return;
    }

    isProcessing = true;
    UI.submitBtn.disabled = true;
    UI.submitBtn.textContent = '正在评分...';

    try {
        const res = await api.post('/game_chat.php', {
            session_id: currentSession.session_id,
            message: text
        });

        // Update Credits
        currentUser.credits = res.remaining_credits;
        updateCreditUI(currentUser.credits);

        // Show Result
        UI.scoreValue.textContent = res.score;
        UI.analysisContent.textContent = res.analysis || "暂无解析";
        UI.bestReplyContent.textContent = res.best_reply || "暂无示范";
        
        showResultModal();

    } catch (err) {
        if (err.message === 'NO_CREDITS' || err.message.includes('体力不足')) {
            showNoCreditModal();
        } else {
            alert('提交失败: ' + err.message);
        }
    } finally {
        isProcessing = false;
        UI.submitBtn.disabled = false;
        UI.submitBtn.textContent = '提交回复';
    }
}

// Share Logic
async function shareToRevive() {
    UI.shareBtn.textContent = '分享中...';
    UI.shareBtn.disabled = true;

    // Simulate share delay
    setTimeout(async () => {
        try {
            await api.post('/user_share.php', { user_id: currentUser.user_id });
            await refreshUserInfo();
            
            UI.noCreditModal.classList.add('hidden');
            alert('复活成功！获得 1 次机会');
        } catch (err) {
            alert('分享失败');
        } finally {
            UI.shareBtn.textContent = '分享给好友 (+1次)';
            UI.shareBtn.disabled = false;
        }
    }, 1500);
}

// Modal Utils
function showResultModal() {
    UI.resultModal.classList.remove('hidden');
    void UI.resultModal.offsetWidth;
    UI.modalContent.classList.remove('translate-y-full');
}

function closeModal() {
    UI.modalContent.classList.add('translate-y-full');
    setTimeout(() => {
        UI.resultModal.classList.add('hidden');
    }, 300);
}

function showNoCreditModal() {
    UI.noCreditModal.classList.remove('hidden');
}

// 打开用户中心
async function openUserCenter() {
    // 先刷新一次用户信息，保证 openid/credits 最新
    await refreshUserInfo();
    UI.ucOpenId.textContent = currentUser.openid || '未知';
    UI.ucCredits.textContent = currentUser.credits;

    // 拉取历史记录
    await loadUserHistory();

    // 显示面板
    UI.userCenterModal.classList.remove('hidden');
    void UI.userCenterModal.offsetWidth;
    UI.ucPanel.classList.remove('translate-y-full');
}

// 关闭用户中心
function closeUserCenter() {
    UI.ucPanel.classList.add('translate-y-full');
    setTimeout(() => {
        UI.userCenterModal.classList.add('hidden');
    }, 300);
}

// 加载历史记录
async function loadUserHistory() {
    try {
        UI.ucHistoryList.innerHTML = '<div class="text-center text-gray-400 py-6">加载中...</div>';
        const data = await api.get(`/user_history.php?user_id=${currentUser.user_id}&limit=20`);
        if (!data || data.length === 0) {
            UI.ucHistoryList.innerHTML = '<div class="text-center text-gray-400 py-6">暂无历史记录</div>';
            return;
        }
        UI.ucHistoryList.innerHTML = '';
        data.forEach(item => {
            const dateStr = new Date(item.created_at).toLocaleString();
            const html = `
                <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                    <div class="text-xs text-gray-400">${dateStr}</div>
                    <div class="mt-1 text-gray-700"><span class="text-gray-500">题目：</span>${item.npc_message || '（无）'}</div>
                    <div class="mt-1 text-gray-700"><span class="text-gray-500">我的回复：</span>${item.user_message || '（无）'}</div>
                    <div class="mt-2 font-bold text-yellow-600">得分：${item.score ?? 0}</div>
                </div>
            `;
            UI.ucHistoryList.innerHTML += html;
        });
    } catch (err) {
        UI.ucHistoryList.innerHTML = '<div class="text-center text-red-500 py-6">加载失败</div>';
    }
}

// 用户中心中的分享充值
async function rechargeByShare() {
    UI.ucRechargeBtn.textContent = '分享中...';
    UI.ucRechargeBtn.disabled = true;
    setTimeout(async () => {
        try {
            await api.post('/user_share.php', { user_id: currentUser.user_id });
            await refreshUserInfo();
            UI.ucCredits.textContent = currentUser.credits;
            alert('分享成功，生命值 +1');
        } catch (err) {
            alert('分享失败');
        } finally {
            UI.ucRechargeBtn.textContent = '生命值充值';
            UI.ucRechargeBtn.disabled = false;
        }
    }, 1200);
}

// Global scope for HTML callbacks
window.switchScenario = switchScenario;
window.refreshTopic = refreshTopic;
window.submitReply = submitReply;
window.closeModal = closeModal;
window.shareToRevive = shareToRevive;
window.openUserCenter = openUserCenter;
window.closeUserCenter = closeUserCenter;
window.rechargeByShare = rechargeByShare;
