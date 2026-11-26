<?php
namespace app\common\lib;

class AiClient
{
    protected $config = [];
    protected $trainingData = [];

    public function __construct($config, $trainingData = [])
    {
        $this->config = $config;
        $this->trainingData = $trainingData;
    }

    public function generate($prompt)
    {
        if (empty($this->config['model_type'])) {
            return ['error' => '模型类型未配置'];
        }
        
        $modelType = $this->config['model_type'];
        
        $fullPrompt = $this->buildFullPrompt($prompt);
        
        switch ($modelType) {
            case 'baidu_wenxin':
                return $this->callBaiduWenxin($fullPrompt);
            case 'doubao':
                return $this->callDoubao($fullPrompt);
            case 'alibaba_tongyi':
                return $this->callTongyi($fullPrompt);
            case 'deepseek':
                return $this->callDeepseek($fullPrompt);
            case 'openai_chatgpt':
                return $this->callChatGPT($fullPrompt);
            case 'google_gemini':
                return $this->callGemini($fullPrompt);
            case 'flash_thinking':
                return $this->callFlashThinking($fullPrompt);
            default:
                return ['error' => '未知模型类型'];
        }
    }

    protected function buildFullPrompt($userQuestion)
    {
        $systemPrompt = "你是一个专业的在线客服助手，请根据以下信息回答用户问题：\n\n";
        
        if (!empty($this->trainingData)) {
            $systemPrompt .= "【参考知识库】\n";
            foreach ($this->trainingData as $data) {
                $typeName = $this->getTypeName($data['type']);
                $systemPrompt .= "{$typeName}：{$data['title']}\n{$data['content']}\n\n";
            }
        }
        
        $systemPrompt .= "【任务要求】\n";
        $systemPrompt .= "1. 根据提供的知识库内容回答用户问题\n";
        $systemPrompt .= "2. 如果知识库中没有相关信息，请礼貌地说明\n";
        $systemPrompt .= "3. 回答要简洁明了，符合客服沟通规范\n";
        $systemPrompt .= "4. 不要使用Markdown格式，使用纯文本\n";
        
        if (!empty($this->config['prompt_template'])) {
            $systemPrompt .= "\n" . $this->config['prompt_template'] . "\n";
        }
        
        $systemPrompt .= "\n【用户问题】\n" . $userQuestion;
        
        return $systemPrompt;
    }

    protected function getTypeName($type)
    {
        $types = [
            'product' => '产品参数',
            'faq' => '常见问题',
            'sales' => '销售话术',
            'other' => '其他信息',
        ];
        return $types[$type] ?? '其他信息';
    }

    protected function callBaiduWenxin($prompt)
    {
        if (empty($this->config['api_key'])) {
            return ['error' => 'API Key未配置'];
        }
        if (empty($this->config['secret_key'])) {
            return ['error' => 'Secret Key未配置'];
        }
        
        $apiKey = $this->config['api_key'];
        $secretKey = $this->config['secret_key'];
        
        $authUrl = 'https://aip.baidubce.com/oauth/2.0/token';
        $authParams = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiKey,
            'client_secret' => $secretKey,
        ];
        
        $authResponse = $this->httpPost($authUrl, $authParams);
        $authData = json_decode($authResponse, true);
        
        if (!isset($authData['access_token'])) {
            return ['error' => '获取百度access_token失败'];
        }
        
        $accessToken = $authData['access_token'];
        $apiUrl = $this->config['api_url'] ?: 'https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro';
        
        $postData = [
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => floatval($this->config['temperature']),
            'max_tokens' => intval($this->config['max_tokens']),
        ];
        
        $response = $this->httpPost($apiUrl . '?access_token=' . $accessToken, json_encode($postData), 'application/json');
        $result = json_decode($response, true);
        
        if (isset($result['result'])) {
            return ['answer' => $result['result']];
        }
        return ['error' => $result['error_msg'] ?? '调用失败'];
    }

    protected function callDoubao($prompt)
    {
        $apiKey = $this->config['api_key'];
        $apiUrl = $this->config['api_url'] ?: 'https://api.doubao.com/api/chat/completions';
        
        $postData = [
            'model' => $this->config['model_name'] ?: 'doubao-pro',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => floatval($this->config['temperature']),
            'max_tokens' => intval($this->config['max_tokens']),
        ];
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];
        
        $response = $this->httpPost($apiUrl, json_encode($postData), 'application/json', $headers);
        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return ['answer' => $result['choices'][0]['message']['content']];
        }
        return ['error' => $result['error']['message'] ?? '调用失败'];
    }

    protected function callTongyi($prompt)
    {
        $apiKey = $this->config['api_key'];
        $apiUrl = $this->config['api_url'] ?: 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation';
        
        $postData = [
            'model' => $this->config['model_name'] ?: 'qwen-turbo',
            'input' => [
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ]
            ],
            'parameters' => [
                'temperature' => floatval($this->config['temperature']),
                'max_tokens' => intval($this->config['max_tokens']),
            ],
        ];
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'X-DashScope-SSE: false',
        ];
        
        $response = $this->httpPost($apiUrl, json_encode($postData), 'application/json', $headers);
        $result = json_decode($response, true);
        
        if (isset($result['output']['text'])) {
            return ['answer' => $result['output']['text']];
        }
        return ['error' => $result['message'] ?? '调用失败'];
    }

    protected function callDeepseek($prompt)
    {
        $apiKey = $this->config['api_key'];
        $apiUrl = $this->config['api_url'] ?: 'https://api.deepseek.com/v1/chat/completions';
        
        $postData = [
            'model' => $this->config['model_name'] ?: 'deepseek-chat',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => floatval($this->config['temperature']),
            'max_tokens' => intval($this->config['max_tokens']),
        ];
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];
        
        $response = $this->httpPost($apiUrl, json_encode($postData), 'application/json', $headers);
        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return ['answer' => $result['choices'][0]['message']['content']];
        }
        return ['error' => $result['error']['message'] ?? '调用失败'];
    }

    protected function callChatGPT($prompt)
    {
        $apiKey = $this->config['api_key'];
        $apiUrl = $this->config['api_url'] ?: 'https://api.openai.com/v1/chat/completions';
        
        $postData = [
            'model' => $this->config['model_name'] ?: 'gpt-4o-mini',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => floatval($this->config['temperature']),
            'max_tokens' => intval($this->config['max_tokens']),
        ];
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];
        
        $response = $this->httpPost($apiUrl, json_encode($postData), 'application/json', $headers);
        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return ['answer' => $result['choices'][0]['message']['content']];
        }
        return ['error' => $result['error']['message'] ?? '调用失败'];
    }

    protected function callGemini($prompt)
    {
        $apiKey = $this->config['api_key'];
        $apiUrl = $this->config['api_url'] ?: 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
        
        $postData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => floatval($this->config['temperature']),
                'maxOutputTokens' => intval($this->config['max_tokens']),
            ],
        ];
        
        $response = $this->httpPost($apiUrl . '?key=' . $apiKey, json_encode($postData), 'application/json');
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return ['answer' => $result['candidates'][0]['content']['parts'][0]['text']];
        }
        return ['error' => $result['error']['message'] ?? '调用失败'];
    }

    protected function callFlashThinking($prompt)
    {
        $apiKey = $this->config['api_key'];
        $apiUrl = $this->config['api_url'] ?: 'https://api.flashthinking.ai/v1/chat/completions';
        
        $postData = [
            'model' => $this->config['model_name'] ?: 'flash-thinking-pro',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => floatval($this->config['temperature']),
            'max_tokens' => intval($this->config['max_tokens']),
        ];
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];
        
        $response = $this->httpPost($apiUrl, json_encode($postData), 'application/json', $headers);
        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return ['answer' => $result['choices'][0]['message']['content']];
        }
        return ['error' => $result['error']['message'] ?? '调用失败'];
    }

    protected function httpPost($url, $data, $contentType = 'application/x-www-form-urlencoded', $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $defaultHeaders = [
            'Content-Type: ' . $contentType,
            'User-Agent: AiClient/1.0',
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return json_encode(['error' => $error]);
        }
        
        curl_close($ch);
        return $response;
    }
}