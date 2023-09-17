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



<?php
//active form
/* @var $this yii\web\View */
/* @var $model \frontend\forms\UploadFileForm */
use yii\widgets\ActiveForm;
?>
 
<?php $form = ActiveForm::begin(['options' => []]) ?>
<?= $form->field($model, 'file')->fileInput() ?>
<button>Submit</button>
<?php ActiveForm::end() ?>


<?
//one file upload
public function actionOneFile()
{
  $model = new UploadFileForm();
 
  if (Yii::$app->request->isPost) {
    $model->file = UploadedFile::getInstance($model, 'file');
    if ($model->upload()) {
      Yii::$app->session->setFlash('success', 'Изображение загружено');
      return $this->refresh();
    }
  }
  return $this->render('index', ['model' => $model]);
}
Можно получить файл не из модели. Например, получить файл при отправке запроса по API:

$file = UploadedFile::getInstanceByName('file');?>
<?
//Загрузка нескольких файлов
class UploadFilesForm extends Model
{
  /**
   * @var UploadedFile[]
   */
  public $files;
 
  public function rules()
  {
    return [
      ['files', 'image',
        'extensions' => ['jpg', 'jpeg', 'png', 'gif'],
        'checkExtensionByMimeType' => true,
        'maxSize' => 512000, // 500 килобайт = 500 * 1024 байта = 512 000 байт
        'tooBig' => 'Limit is 500KB',
        'maxFiles' => 5
      ]
    ];
  }
 //сохранение файлов
  public function upload()
  {
    if ($this->validate()) {
      foreach ($this->files as $file) {
        $file->saveAs('uploads/' . $this->randomFileName($file->extension));
      }
      return true;
    } else {
      return false;
    }
  }
 //генерация имени файла
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
<?php
//Представление:


use yii\widgets\ActiveForm;
/* @var $this yii\web\View */
/* @var $model \frontend\forms\UploadFileForm */
 $form = ActiveForm::begin(['options' => []]);
echo $form->field($model, 'files[]')->fileInput(['multiple' => true, 'accept' => 'image/*']) ?>
  <button>Submit</button>
  <?php ActiveForm::end() ?>
<?//Контроллер:

public function actionMultiFile()
{
  $model = new UploadFilesForm();
 
  if (Yii::$app->request->isPost) {
    $model->files = UploadedFile::getInstances($model, 'files');
    if ($model->upload()) {
      Yii::$app->session->setFlash('success', 'Изображения загружены');
      return $this->refresh();
    }
  }
  return $this->render('index', ['model' => $model]);
}
Изображение для новости
В данном примере мы немного отойдём от привычной для новичков работы с моделями. Мы разделим нашу сущность (в данном случае это новость) на class NewsForm extends Model и class News extends ActiveRecord. NewsForm принимает данные и валидирует их. News работает с базой данных.

//Миграция:
//накатываем
public function up()
{
  $tableOptions = null;
  if ($this->db->driverName === 'mysql') {
    $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
  }
 
  $this->createTable('{{%news}}', [
    'id' => $this->primaryKey(),
    'name' => $this->string()->notNull()->unique(),
    'content' => $this->text(),
    'slug' => $this->string()->notNull()->unique(),
    'image' => $this->string()->defaultValue(''),
    'created_at' => $this->integer()->notNull(),
  ], $tableOptions);
  $this->createIndex('{{%idx-new-slug}}', '{{%news}}', 'slug', true);
}
 
public function down()
//отменяем накатку{
  $this->dropTable('{{%news}}');
}
Класс NewsForm:

class NewsForm extends Model{
  /**
 * @var UploadedFile
 * Здесь хранится экземпляр класса UploadedFile
 */
public $image;
public $name;
public $content;
public $slug;
public $created_at;
private $_model;
 
public function __construct(News $model = null, $config = []) {
  if ($model) {
    $this->name = $model->name;
    $this->content = $model->content;
    $this->slug = $model->slug;
    $this->created_at = $model->created_at;
    $this->_model = $model;
  }
  parent::__construct($config);
}
 
public function rules()
{
  return [
    [['name'], 'required'],
    [['name', 'slug'], 'string', 'max' => 255],
    ['content', 'string'],
    ['slug', SlugValidator::class],
    [['name', 'slug'], 'unique',
       'targetClass' => News::class,
       'filter' => $this->_model ? ['<>', 'id', $this->_model->id] : null
    ],
    [['image'], 'image',
       'extensions' => ['jpg', 'jpeg', 'png', 'gif'],
       'checkExtensionByMimeType' => true,
       'maxSize' => 512000, // 500 килобайт = 500 * 1024 байта = 512 000 байт
       'tooBig' => 'Limit is 500KB'
    ],
  ];
}
 
public function uploadImage(UploadedFile $image, $currentImage = null)
{
  if (!is_null($currentImage))
    $this->deleteCurrentImage($currentImage);
  $this->image = $image;
  if($this->validate())
    return $this->saveImage();
  return false;
}
 
 
private function getUploadPath()
{
  return Yii::$app->params['uploadPath'] . 'news/';
}
 
 
/**
 * @return string
 */
public function generateFileName(): string
{
  do {
    $name = substr(md5(microtime() . rand(0, 1000)), 0, 20);
    $file = strtolower($name .'.'. $this->image->extension);
  } while (file_exists($file));
  return $file;
}
 
public function deleteCurrentImage($currentImage)
{
  if ($currentImage && $this->fileExists($currentImage)) {
    unlink($this->getUploadPath() . $currentImage);
  }
}
 
/**
 * @param $currentFile
 * @return bool
 */
public function fileExists($currentFile): bool
{
  $file = $currentFile ? $this->getUploadPath() . $currentFile : null;
  return file_exists($file);
}
 
/**
 * @return string
 */
public function saveImage(): string
{
  $filename = $this->generateFilename();
  $this->image->saveAs($this->getUploadPath() . $filename);
  return $filename;
}	
}
Конфигурация путей (*/config/params.php):

// ...
'uploadHostInfo' => 'http://mysite.local/upload', // Показываем отсюда
'uploadPath' => dirname(__DIR__, 2) . '/frontend/web/upload/', // Загружаем сюда
Класс News:

class News extends ActiveRecord
{
  public static function tableName() {
    return '{{%news}}';
  }
 
  public function getImagePath()
  {
    if ($this->image)
      return $this->getImage($this->image);
    return 'https://via.placeholder.com/300x200'; // Default image
  }
 
  private function getImage(string $filename): string
  {
    return Yii::$app->params['uploadHostInfo'] . 'news/' . $filename;
  }
 
  public function beforeDelete()
  {
    $this->deleteImage();
    return parent::beforeDelete();
  }
 
  public function deleteImage()
  {
    $form = new NewsForm();
    $form->deleteCurrentImage($this->image);
  }
}
Виды

news_form.php

<?php
 
use yii\helpers\Html;
use yii\widgets\ActiveForm;
 
/* @var $this yii\web\View */
/* @var $model common\models\News */
/* @var $modelForm \common\forms\NewsForm */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="news-form">
  <?php $form = ActiveForm::begin(['id' => 'news-form', 'options' => []]); ?>
  <?= $form->field($modelForm, 'name')->textInput(['maxlength' => true]) ?>
  <?= $form->field($modelForm, 'content')->textarea(['rows' => 10]) ?>
  <?= $form->field($modelForm, 'slug')->textInput(['maxlength' => true]) ?>
  <?= $form->field($modelForm, 'created_at')->textInput(['maxlength' => true]) ?>
  <div class="new-image">
    <img src="<?= $model->getImagePath() ?>" alt="" width="300">
  </div>
  <?= $form->field($modelForm, 'image')->fileInput() ?>
  <div class="form-group">
      <?= Html::submitButton('Save', ['class' => 'btn btn-default']) ?>
  </div>
  <?php ActiveForm::end(); ?>
</div>
create.php

<?php
 
use yii\helpers\Html;
 
/* @var $this yii\web\View */
/* @var $modelForm \common\forms\NewsForm */
/* @var $model \common\models\News */
 
$this->title = 'Create New';
$this->params['breadcrumbs'][] = ['label' => 'News', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="new-create">
  <h1><?= Html::encode($this->title) ?></h1>
  <?= $this->render('_form', [
    'model' => $model,
    'modelForm' => $modelForm
  ]) ?>
</div>
index.php

<?php
use common\models\News;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
 
$this->title = 'News';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="new-index">
  <h1><?= Html::encode($this->title) ?></h1>
  <?php Pjax::begin(); ?>
  <p>
      <?= Html::a('Create New', ['create'], ['class' => 'btn btn-success']) ?>
  </p>
  <?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
      ['class' => 'yii\grid\SerialColumn'],
      'name',
      [
        'value' => function (News $model) {
          return Html::img($model->getImagePath(), ['width' => 100, 'alt' => $model->name]);
        },
        'label' => 'Image',
        'format' => 'raw'
      ],
      ['class' => 'yii\grid\ActionColumn'],
    ]
  ]); ?>
  <?php Pjax::end(); ?>
</div>
update.php

<?php
use yii\helpers\Html;
/* @var $this yii\web\View */
/* @var $modelForm \common\forms\NewsForm */
/* @var $model \common\models\News */
 
$this->title = 'Update New: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'News', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="new-update">
  <h1><?= Html::encode($this->title) ?></h1>
  <?= $this->render('_form', [
    'model' => $model,
    'modelForm' => $modelForm
  ]) ?>
</div>
view.php

<?php
use common\models\News;
use yii\helpers\Html;
use yii\widgets\DetailView;
 
/* @var $this yii\web\View */
/* @var $model common\models\News */
 
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'News', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="new-view">
<h1><?= Html::encode($this->title) ?></h1>
<p>
  <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
  &nbsp;&nbsp;|&nbsp;&nbsp;
  <?= Html::a('Delete', ['delete', 'id' => $model->id], [
    'class' => 'btn btn-danger',
    'data' => [
      'confirm' => 'Are you sure you want to delete this item?',
      'method' => 'post'
    ]
  ]) ?>
  <?= Html::a('Delete Image', ['delete-image', 'id' => $model->id], [
    'class' => 'btn btn-danger',
    'data' => [
      'confirm' => 'Are you sure you want to delete this item?',
      'method' => 'post'
    ]
  ]) ?>
</p>
<?= DetailView::widget([
  'model' => $model,
  'attributes' => [
    'id',
    'name',
    'content:raw',
    'slug',
    'created_at:date',
    [
      'value' => function (News $model) {
        return Html::img($model->getImagePath(), ['width' => 200, 'alt' => $model->name]);
      },
      'label' => 'Image',
      'format' => 'raw'
    ]
  ]
]) ?>
</div>
Контроллер:
<?
// Создание новости
public function actionCreate()
{
 $model = new News();
 $modelForm = new NewsForm();
 if ($modelForm->load(Yii::$app->request->post()) && $modelForm->validate()) {
  if ($image = UploadedFile::getInstance($modelForm, 'image')) {
   $model->image = $modelForm->uploadImage($image);
  }
  $model->name = $modelForm->name;
  $model->slug = $modelForm->slug ?: Inflector::slug($model->name);
  $model->created_at = $modelForm->created_at ?: time();
  if($model->save()) {
   return $this->redirect(['view', 'id' => $model->id]);
  }
 }
 
 return $this->render('create', [
  'model' => $model,
  'modelForm' => $modelForm
 ]);
}?>
 
 <?
// Редактирование новости
public function actionUpdate($id)
{
 $model = $this->findModel($id);
 $modelForm = new NewsForm($model);
 if ($modelForm->load(Yii::$app->request->post()) && $modelForm->validate()) {
    if ($image = UploadedFile::getInstance($modelForm, 'image')) {
      $model->image = $modelForm->uploadImage($image, $model->image);
    }
  $model->name = $modelForm->name;
  $model->slug = $modelForm->slug ?: Inflector::slug($model->name);
  if($model->save()) {
   Yii::$app->session->setFlash('success', 'Все прошло удачно');
   return $this->redirect(['view', 'id' => $model->id]);
  }
 }
 
 return $this->render('update', [
  'model' => $model,
  'modelForm' => $modelForm,
 ]);
}
 
 
// Удаление изображения
public function actionDeleteImage($id)
{
  $model = $this->findModel($id);
  if (Yii::$app->request->isPost) {
    $model->deleteImage();
    $model->image = '';
    if($model->save()) {
      Yii::$app->session->setFlash('success', 'Изображение удалено!');
    }
  }
 
  return $this->render('view', [
    'model' => $model,
  ]);
}