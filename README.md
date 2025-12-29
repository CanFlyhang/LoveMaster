# 谁是恋爱大师？ (Who Is The Love Master?)

## 📖 项目简介
“谁是恋爱大师？”是一款基于大语言模型（LLM）的 Web 响应式互动游戏。玩家需要在模拟的恋爱或职场场景中，扮演合格的伴侣或角色，回答 NPC（由 AI 扮演）提出的刁钻问题。系统会通过大模型 API 对玩家的回答进行实时评分、分析，并给出最佳回复建议。

本项目旨在通过趣味性的互动，帮助用户提升情商（EQ）和沟通技巧。

## ✨ 核心功能
- **多场景模拟**：支持恋爱（男/女视角）、职场等多种对话场景。
- **AI 智能评分**：调用 DeepSeek 等大模型 API，对用户回答进行深度语义分析和打分。
- **实时反馈**：提供详细的评分理由和“高情商”回复建议。
- **闯关挑战**：设置高难度阈值，挑战成为“恋爱大师”。
- **积分系统**：每日限制挑战次数（默认 10 次），支持分享裂变获取更多机会。
- **排行榜**：实时查看各场景下的高分玩家。
- **响应式设计**：完美适配移动端和桌面端。

## 🛠 技术栈
- **前端**：HTML5, CSS3, JavaScript (原生, SPA 架构)
- **后端**：PHP (7.4+)
- **数据库**：MySQL (5.7+)
- **AI 模型**：DeepSeek API (兼容 OpenAI 格式)

## 🚀 快速开始

### 环境要求
- PHP >= 7.4
- MySQL >= 5.7
- Web 服务器 (Nginx/Apache)

### 安装步骤

1. **克隆项目**
   ```bash
   git clone https://github.com/your-username/who-is-love-master.git
   cd who-is-love-master
   ```

2. **配置数据库与 API**
   打开 `api/config.php` 文件，填入你的数据库信息和 LLM API Key：
   ```php
   // api/config.php
   
   // 数据库配置
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'high_eq_game');

   // LLM 配置
   define('LLM_API_KEY', 'your-api-key-here'); 
   ```

3. **初始化数据库**
   在项目根目录下运行初始化脚本（确保 PHP 已配置在环境变量中）：
   ```bash
   php scripts/init_db.php
   ```
   或者手动创建一个名为 `high_eq_game` 的数据库，并导入 `sql/schema.sql` 文件。

4. **启动服务**
   将项目目录配置到 Nginx/Apache 的 Web 根目录，或使用 PHP 内置服务器进行快速测试：
   ```bash
   php -S localhost:8000
   ```

5. **访问项目**
   在浏览器中打开 `http://localhost:8000` 即可开始体验。

## 📂 目录结构
```
.
├── api/                # PHP 后端接口逻辑
│   ├── config.php      # 核心配置文件
│   ├── llm_service.php # 大模型调用服务
│   ├── prompts.php     # 提示词管理
│   └── ...
├── css/                # 样式文件
├── js/                 # 前端逻辑脚本
├── scripts/            # 数据库初始化与测试脚本
├── sql/                # 数据库结构 SQL
├── index.html          # 游戏主页
├── rank.html           # 排行榜页面
└── README.md           # 项目说明文档
```

## 🤝 贡献指南
欢迎提交 Issue 和 Pull Request！
1. Fork 本仓库
2. 新建 Feat_xxx 分支
3. 提交代码
4. 新建 Pull Request

## 📄 开源协议
本项目采用 MIT 协议开源。

