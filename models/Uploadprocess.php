<?class UploadFileModel extends Model
{
  /**
   * @var UploadedFile
   */
  public $file;
 
  public function rules()
  {
    return [
      ['file', 'image',
        'extensions' => ['jpg', 'jpeg', 'png', 'gif'],
        'checkExtensionByMimeType' => true,
        'maxSize' => 512000, // 500 килобайт = 500 * 1024 байта = 512 000 байт
        'tooBig' => 'Limit is 500KB'
      ],
    ];
  }
 
  public function upload()
  {
    if ($this->validate()) {
      $dir = 'uploads/'; // Директория - должна быть создана
      $name = $this->randomFileName($this->file->extension);
      $file = $dir . $name;
      $this->file->saveAs($file); // Сохраняем файл
      return true;
    } else {
      return false;
    }
  }
 
  private function randomFileName($extension = false)
  {
    $extension = $extension ? '.' . $extension : '';
    do {
      $name = md5(microtime() . rand(0, 1000));
      $file = $name . $extension;
    } while (file_exists($file));
    return $file;
  }
}
