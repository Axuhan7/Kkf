<?php
namespace app\admin\controller;

use app\admin\model\AiConfig;
use app\admin\model\AiTraining;
use app\common\lib\AiClient;
use think\Db;
use think\Log;

class Ai extends Base
{
    public function index()
    {
        $login = session('Msg');
        $businessId = $login['business_id'];
        
        if ($this->request->isGet() && ($this->request->get('id') || $this->request->get('training_id'))) {
            if ($this->request->get('id')) {
                $config = AiConfig::where('id', $this->request->get('id'))->find();
                return ['code' => 0, 'data' => $config ? $config->toArray() : []];
            }
            if ($this->request->get('training_id')) {
                $training = AiTraining::where('id', $this->request->get('training_id'))->find();
                return ['code' => 0, 'trainingData' => $training ? $training->toArray() : []];
            }
        }
        
        $aiConfigs = AiConfig::where('business_id', $businessId)->select();
        $aiTrainings = AiTraining::where('business_id', $businessId)->order('sort desc')->select();
        
        $business = Db::name('business')->where('id', $businessId)->find();
        
        $this->assign('aiConfigs', $aiConfigs);
        $this->assign('aiTrainings', $aiTrainings);
        $this->assign('business', $business);
        $this->assign('modelTypes', AiConfig::getModelTypeList());
        $this->assign('trainingTypes', AiTraining::getTypeList());
        $this->assign('title', 'AI客服配置');
        $this->assign('part', '系统设置');
        
        return $this->fetch();
    }

    public function saveConfig()
    {
        try {
            $login = session('Msg');
            $businessId = $login['business_id'];
            $post = $this->request->post();
            
            unset($post['__token__'], $post['create_time'], $post['update_time']);
            
            $post['business_id'] = $businessId;
            
            if (!empty($post['id'])) {
                $result = AiConfig::where('id', $post['id'])->update($post);
            } else {
                $result = AiConfig::create($post);
            }
            
            if ($post['is_default'] == 1) {
                AiConfig::where('business_id', $businessId)->where('id', '<>', $post['id'] ?? 0)->update(['is_default' => 0]);
            }
            
            return json($result !== false ? ['code' => 0, 'msg' => '保存成功'] : ['code' => 1, 'msg' => '保存失败']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }

    public function deleteConfig()
    {
        try {
            $post = $this->request->post();
            $result = AiConfig::where('id', $post['id'])->delete();
            return json($result !== false ? ['code' => 0, 'msg' => '删除成功'] : ['code' => 1, 'msg' => '删除失败']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }

    public function testConfig()
    {
        $id = $this->request->post('id', 0);
        if (empty($id)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        
        $config = AiConfig::where('id', $id)->find();
        if (!$config) {
            return json(['code' => 1, 'msg' => '配置不存在']);
        }
        
        $configData = $config->toArray();
        
        if (empty($configData['api_key'])) {
            return json(['code' => 1, 'msg' => '请先配置API Key']);
        }
        
        if (empty($configData['model_type'])) {
            return json(['code' => 1, 'msg' => '模型类型未配置']);
        }
        
        $modelType = $configData['model_type'];
        $apiKey = $configData['api_key'];
        $apiUrl = !empty($configData['api_url']) ? $configData['api_url'] : '';
        $secretKey = !empty($configData['secret_key']) ? $configData['secret_key'] : '';
        $modelName = !empty($configData['model_name']) ? $configData['model_name'] : '';
        $temperature = floatval($configData['temperature'] ?: 0.7);
        $maxTokens = intval($configData['max_tokens'] ?: 2000);
        
        $prompt = '你好';
        $apiResponse = '';
        $httpCode = 0;
        $curlError = '';
        
        switch ($modelType) {
            case 'baidu_wenxin':
                if (empty($secretKey)) {
                    return json(['code' => 1, 'msg' => '文心一言需要配置Secret Key']);
                }
                $authUrl = 'https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=' . urlencode($apiKey) . '&client_secret=' . urlencode($secretKey);
                $ch = curl_init($authUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $authResp = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    return json(['code' => 1, 'msg' => '获取token失败：' . $curlError]);
                }
                $authData = json_decode($authResp, true);
                if (!isset($authData['access_token'])) {
                    return json(['code' => 1, 'msg' => '获取access_token失败，请检查API Key和Secret Key是否正确']);
                }
                
                $chatUrl = $apiUrl ?: 'https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro';
                $postData = json_encode([
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => $temperature,
                    'max_output_tokens' => $maxTokens,
                ]);
                $ch = curl_init($chatUrl . '?access_token=' . $authData['access_token']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                $apiResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    return json(['code' => 1, 'msg' => 'API请求失败：' . $curlError]);
                }
                $result = json_decode($apiResponse, true);
                if (isset($result['result'])) {
                    return json(['code' => 0, 'msg' => '测试成功', 'data' => $result['result']]);
                }
                return json(['code' => 1, 'msg' => 'API返回错误：' . ($result['error_msg'] ?? '未知错误')]);
                
            case 'doubao':
                $url = $apiUrl ?: 'https://ark.cn-beijing.volces.com/api/v3/chat/completions';
                $postData = json_encode([
                    'model' => $modelName ?: 'doubao-pro',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ]);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ]);
                $apiResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    return json(['code' => 1, 'msg' => 'API请求失败：' . $curlError]);
                }
                $result = json_decode($apiResponse, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    return json(['code' => 0, 'msg' => '测试成功', 'data' => $result['choices'][0]['message']['content']]);
                }
                return json(['code' => 1, 'msg' => 'API返回错误：' . ($result['error']['message'] ?? 'HTTP状态码：' . $httpCode)]);
                
            case 'deepseek':
                $url = $apiUrl ?: 'https://api.deepseek.com/v1/chat/completions';
                $postData = json_encode([
                    'model' => $modelName ?: 'deepseek-chat',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ]);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ]);
                $apiResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    return json(['code' => 1, 'msg' => 'API请求失败：' . $curlError]);
                }
                $result = json_decode($apiResponse, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    return json(['code' => 0, 'msg' => '测试成功', 'data' => $result['choices'][0]['message']['content']]);
                }
                return json(['code' => 1, 'msg' => 'API返回错误：' . ($result['error']['message'] ?? 'HTTP状态码：' . $httpCode)]);
                
            case 'openai_chatgpt':
                $url = $apiUrl ?: 'https://api.openai.com/v1/chat/completions';
                $postData = json_encode([
                    'model' => $modelName ?: 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ]);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ]);
                $apiResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    return json(['code' => 1, 'msg' => 'API请求失败：' . $curlError]);
                }
                $result = json_decode($apiResponse, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    return json(['code' => 0, 'msg' => '测试成功', 'data' => $result['choices'][0]['message']['content']]);
                }
                return json(['code' => 1, 'msg' => 'API返回错误：' . ($result['error']['message'] ?? 'HTTP状态码：' . $httpCode)]);
                
            case 'alibaba_tongyi':
                $url = $apiUrl ?: 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation';
                $postData = json_encode([
                    'model' => $modelName ?: 'qwen-turbo',
                    'input' => ['messages' => [['role' => 'user', 'content' => $prompt]]],
                    'parameters' => ['temperature' => $temperature, 'max_tokens' => $maxTokens],
                ]);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ]);
                $apiResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    return json(['code' => 1, 'msg' => 'API请求失败：' . $curlError]);
                }
                $result = json_decode($apiResponse, true);
                if (isset($result['output']['text'])) {
                    return json(['code' => 0, 'msg' => '测试成功', 'data' => $result['output']['text']]);
                }
                return json(['code' => 1, 'msg' => 'API返回错误：' . ($result['message'] ?? 'HTTP状态码：' . $httpCode)]);
                
            case 'google_gemini':
                $url = ($apiUrl ?: 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent') . '?key=' . $apiKey;
                $postData = json_encode([
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => $temperature, 'maxOutputTokens' => $maxTokens],
                ]);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                $apiResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    return json(['code' => 1, 'msg' => 'API请求失败：' . $curlError]);
                }
                $result = json_decode($apiResponse, true);
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return json(['code' => 0, 'msg' => '测试成功', 'data' => $result['candidates'][0]['content']['parts'][0]['text']]);
                }
                return json(['code' => 1, 'msg' => 'API返回错误：' . ($result['error']['message'] ?? 'HTTP状态码：' . $httpCode)]);
                
            case 'flash_thinking':
                $url = $apiUrl ?: 'https://api.flashthinking.ai/v1/chat/completions';
                $postData = json_encode([
                    'model' => $modelName ?: 'flash-thinking-pro',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ]);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ]);
                $apiResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    return json(['code' => 1, 'msg' => 'API请求失败：' . $curlError]);
                }
                $result = json_decode($apiResponse, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    return json(['code' => 0, 'msg' => '测试成功', 'data' => $result['choices'][0]['message']['content']]);
                }
                return json(['code' => 1, 'msg' => 'API返回错误：' . ($result['error']['message'] ?? 'HTTP状态码：' . $httpCode)]);
                
            default:
                return json(['code' => 1, 'msg' => '未知模型类型：' . $modelType]);
        }
    }

    public function saveTraining()
    {
        try {
            $login = session('Msg');
            $businessId = $login['business_id'];
            $post = $this->request->post();
            
            unset($post['__token__'], $post['create_time'], $post['update_time']);
            
            $post['business_id'] = $businessId;
            
            if (!empty($post['id'])) {
                $result = AiTraining::where('id', $post['id'])->update($post);
            } else {
                $result = AiTraining::create($post);
            }
            
            return json($result !== false ? ['code' => 0, 'msg' => '保存成功'] : ['code' => 1, 'msg' => '保存失败']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }

    public function deleteTraining()
    {
        try {
            $post = $this->request->post();
            $result = AiTraining::where('id', $post['id'])->delete();
            return json($result !== false ? ['code' => 0, 'msg' => '删除成功'] : ['code' => 1, 'msg' => '删除失败']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }

    public function toggleTraining()
    {
        try {
            $post = $this->request->post();
            $training = AiTraining::where('id', $post['id'])->find();
            if ($training) {
                $training->is_enabled = $training->is_enabled == 1 ? 0 : 1;
                $training->update_time = time();
                $training->save();
                return json(['code' => 0, 'msg' => '操作成功']);
            }
            return json(['code' => 1, 'msg' => '操作失败']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '操作失败：' . $e->getMessage()]);
        }
    }

    public function saveBusinessSetting()
    {
        try {
            $login = session('Msg');
            $businessId = $login['business_id'];
            $post = $this->request->post();
            
            unset($post['__token__']);
            
            $result = Db::name('business')->where('id', $businessId)->update($post);
            
            return json($result !== false ? ['code' => 0, 'msg' => '保存成功'] : ['code' => 1, 'msg' => '保存失败']);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                return json(['code' => 2, 'msg' => '数据库字段不存在，请先执行SQL脚本']);
            }
            return json(['code' => 1, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }

    public function aiReply()
    {
        try {
            $login = session('Msg');
            $businessId = $login['business_id'];
            $post = $this->request->post();
            
            $business = Db::name('business')->where('id', $businessId)->find();
            
            if ($business['ai_service_state'] != 'open') {
                return json(['code' => 1, 'msg' => 'AI客服未开启']);
            }
            
            $aiConfig = AiConfig::getDefaultModel($businessId);
            
            if (!$aiConfig) {
                return json(['code' => 1, 'msg' => '未配置默认AI模型']);
            }
            
            $trainingData = AiTraining::getEnabledData($businessId)->toArray();
            
            $aiClient = new AiClient($aiConfig->toArray(), $trainingData);
            $result = $aiClient->generate($post['content']);
            
            if (isset($result['error'])) {
                Log::error('AI回复失败: ' . $result['error']);
                return json(['code' => 1, 'msg' => 'AI回复失败']);
            }
            
            return json(['code' => 0, 'msg' => 'success', 'data' => $result['answer']]);
        } catch (\Exception $e) {
            Log::error('AI回复异常: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => 'AI回复异常：' . $e->getMessage()]);
        }
    }
}