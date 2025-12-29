<?php
// api/llm_service.php
require_once 'config.php';
require_once 'prompts.php';

class LLMService {
    
    public function generateTopic($scenarioType) {
        global $PROMPTS;
        if (!defined('LLM_API_KEY') || empty(LLM_API_KEY)) {
            return $this->mockTopic($scenarioType);
        }

        $systemPrompt = $PROMPTS[$scenarioType] ?? null;
        if (!$systemPrompt) {
            return "场景错误";
        }

        $messages = [
            $systemPrompt,
            ['role' => 'user', 'content' => '请给我出一个新的题目，严格按照【任务一】的要求。直接返回题目内容，不需要JSON格式。']
        ];

        $result = $this->callAPI($messages, 0.8);
        if ($result && isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }
        return $this->mockTopic($scenarioType);
    }

    public function getResponse($scenarioType, $history, $userMessage) {
        global $PROMPTS;
        if (!defined('LLM_API_KEY') || empty(LLM_API_KEY)) {
            return $this->mockResponse($scenarioType, $userMessage);
        }

        $systemPrompt = $PROMPTS[$scenarioType];
        $messages = [$systemPrompt];
        
        // 转换历史记录格式
        foreach ($history as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }

        $messages[] = ['role' => 'user', 'content' => "我的回答是：{$userMessage}。请严格按照【任务二】的要求进行评分和分析。"];

        // 尝试调用 API
        $payload = [
            'model' => LLM_MODEL,
            'messages' => $messages,
            'temperature' => 0.7,
            'response_format' => ['type' => 'json_object']
        ];

        // DeepSeek 可能不支持 response_format，如果报错可以移除
        // 这里简单处理：如果返回不是 JSON，尝试清洗
        $result = $this->callAPI($messages, 0.7, true);
        
        if ($result && isset($result['choices'][0]['message']['content'])) {
            $content = $result['choices'][0]['message']['content'];
            
            // 尝试提取 JSON
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $jsonContent = $matches[0];
                $json = json_decode($jsonContent, true);
                if ($json) return $json;
            }
            
            // 如果正则提取失败，尝试直接解析
            $json = json_decode($content, true);
            if ($json) return $json;

            // 如果还是失败，记录原始返回以便调试
            return [
                'score' => 60,
                'analysis' => "解析失败，原始返回：" . substr($content, 0, 200) . "...",
                'best_reply' => "请重试"
            ];
        }

        return $this->mockResponse($scenarioType, $userMessage);
    }

    private function callAPI($messages, $temperature = 0.7, $jsonMode = false) {
        $data = [
            'model' => LLM_MODEL,
            'messages' => $messages,
            'temperature' => $temperature,
            'stream' => false
        ];
        
        if ($jsonMode && strpos(LLM_MODEL, 'deepseek') === false) {
             $data['response_format'] = ['type' => 'json_object'];
        }

        $options = [
            'http' => [
                'header'  => [
                    "Content-Type: application/json",
                    "Authorization: Bearer " . LLM_API_KEY
                ],
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 60, // 增加超时时间
                'ignore_errors' => true // 获取错误状态码的响应体
            ]
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents(LLM_API_URL, false, $context);

        if ($result === FALSE) {
            $error = error_get_last();
            error_log("API Error: " . ($error['message'] ?? 'Unknown error'));
            return null;
        }

        return json_decode($result, true);
    }

    private function mockTopic($scenarioType) {
        $topics = [
            'dating_male' => "早上我熬了小米粥，你出门前记得喝一碗，胃不好别空着肚子上班。你昨天说好了陪我看电影，结果又加班到十点！我坐在电影院门口等了半小时，朋友圈里都是情侣一起的照片，你知道我有多委屈吗？"
        ];
        return $topics[$scenarioType] ?? "题目生成失败";
    }

    private function mockResponse($scenarioType, $userMessage) {
        return [
            'score' => 60,
            'analysis' => "（Mock）深度解析：API未配置或调用失败。您的回答是：{$userMessage}。在真实环境中，我会根据这个回答给出具体的情绪分析。",
            'best_reply' => "（Mock）满分示范：宝贝对不起，昨天让你受委屈了..."
        ];
    }
}
?>
