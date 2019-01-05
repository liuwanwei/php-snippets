<?php

/**
 * 只封装下载文件接口
 */

namespace common\helpers;

class DownloadStream{
  /**
   * 将文件输出供用户下载（自动将文件流返回给用户，触发弹出下载文件窗口）
   *
   * @param string $filePath  要下载的文件在服务器硬盘上的路径
   * @param string $filename  生成的下载文件名字
   * @return void
   */
  public static function createStream(string $filePath, string $filename = null)
  {
    if ($filename == null) {
      // $filename = basename($filePath); 对 multi-bytes 数据支持的不行，必须提前设置 locale，所以换了下面方法
      $parts = explode('/', $filePath);
      $filename = end($parts);
    }
    
    header('Content-Description: File Transfer');
    header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
		// 输出文件前清除输出缓冲区，否则输出的文件会不正确
    ob_clean();
    flush();
    readfile($filePath);
  }
}
