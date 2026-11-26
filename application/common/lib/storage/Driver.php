<?php
/**
 * @copyright Powered by 在线客服系统
 * Created by PhpStorm.
 * User: www.yanshi.fun
 * Date: 2020/6/17
 * Time: 15:33
 */
namespace app\common\lib\storage;

use app\common\lib\Storage;
use app\platform\model\Option;

abstract class Driver
{
    protected $options = [];
    protected $url;
    protected $thumbUrl;
    protected $saveFileFolderl;
    protected $saveThumbFolder;
    protected $saveFileName;
    protected $business_id;
    protected $extension;

    public $file;

    /**
     * 上传
     * @param $name
     * @return mixed
     */
    abstract public function put();

    public function __construct()
    {
        $this->business_id = session('Msg.business_id') ? session('Msg.business_id') : 0;
        $this->file = request()->file(Storage::$variable);
        $this->extension = strtolower(pathinfo($this->file->getInfo('name'), PATHINFO_EXTENSION));
        $option = Option::getList('image_size,file_size,video_size,audio_size', 0, 'admin');
        if (!$this->file->checkExt(['txt','jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp','rar','zip','mp4','ogg','mov','mp3','wav','apk','pdf','xls','doc','ppt','xlsx','docx','pptx','avi','mkv','flv','wmv'])) {
            throw new StorageException('不支持的文件类型');
        };
        // U2 修复：MIME 校验，防止伪造扩展名（保留 zip/rar/apk 上传）
        $this->validateMime();
        if (in_array($this->extension,['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
            $type = 'images';
            if (isset($option['image_size']) && is_numeric($option['image_size'])) {
                if (!$this->file->check(['size'=>1024*1024*$option['image_size']])) {
                    throw new StorageException("图片大小受限，最大为:".$option['image_size']."MB");
                };
            }
        } elseif (in_array($this->extension,['mp4','mov','avi','mkv','flv','wmv'])) {
            $type = 'video';
            $videoSize = isset($option['video_size']) && is_numeric($option['video_size']) ? $option['video_size'] : (isset($option['file_size']) && is_numeric($option['file_size']) ? $option['file_size'] : null);
            if ($videoSize !== null) {
                if (!$this->file->check(['size'=>1024*1024*$videoSize])) {
                    throw new StorageException("视频大小受限，最大为:".$videoSize."MB");
                };
            }
        } elseif (in_array($this->extension,['mp3','wav','ogg'])) {
            $type = 'audio';
            $audioSize = isset($option['audio_size']) && is_numeric($option['audio_size']) ? $option['audio_size'] : (isset($option['file_size']) && is_numeric($option['file_size']) ? $option['file_size'] : null);
            if ($audioSize !== null) {
                if (!$this->file->check(['size'=>1024*1024*$audioSize])) {
                    throw new StorageException("音频大小受限，最大为:".$audioSize."MB");
                };
            }
        } else {
            $type = 'files';
            if (isset($option['file_size']) && is_numeric($option['file_size'])) {
                if (!$this->file->check(['size'=>1024*1024*$option['file_size']])) {
                    throw new StorageException("文件大小受限，最大为:".$option['file_size']."MB");
                };
            }
        }
        $this->saveFileFolder = "/upload/{$type}/{$this->business_id}";
        $this->saveFileName = sha1($this->file->getInfo('tmp_name')) . '.' . $this->extension;
    }

    public function save()
    {
        //todo 保存到数据库
        return [
            'url' => $this->url
        ];
    }

    /**
     * U2 修复：MIME 校验，防止伪造扩展名上传危险文件
     * 保留 zip/rar/apk 文件格式，但对所有上传文件做内容类型校验
     */
    protected function validateMime()
    {
        $tmpName = $this->file->getInfo('tmp_name');
        if (!is_file($tmpName) || !function_exists('finfo_open')) {
            return;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        // 危险 MIME 类型黑名单（无论扩展名如何都拒绝）
        $dangerousMimes = [
            'text/html', 'application/xhtml+xml', 'application/javascript',
            'text/javascript', 'application/x-php', 'text/x-php',
            'application/x-httpd-php', 'application/x-sh', 'application/x-csh',
            'text/x-shellscript', 'application/x-perl', 'application/x-python',
        ];
        if (in_array($mime, $dangerousMimes, true)) {
            throw new StorageException('检测到危险的文件类型');
        }

        // 按扩展名类别校验 MIME 是否匹配
        if (in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'], true)) {
            if (strpos($mime, 'image/') !== 0) {
                throw new StorageException('图片文件类型不匹配');
            }
        } elseif (in_array($this->extension, ['mp4', 'mov', 'avi', 'mkv', 'flv', 'wmv'], true)) {
            if (strpos($mime, 'video/') !== 0) {
                throw new StorageException('视频文件类型不匹配');
            }
        } elseif (in_array($this->extension, ['mp3', 'wav', 'ogg'], true)) {
            $allowedAudioMimes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/ogg', 'application/ogg'];
            if (!in_array($mime, $allowedAudioMimes, true)) {
                throw new StorageException('音频文件类型不匹配');
            }
        }
        // zip/rar/apk/pdf/xls/doc/ppt/txt 等类型：危险 MIME 已在黑名单中拒绝，此处不再做严格校验
    }
}