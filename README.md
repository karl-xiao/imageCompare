# imageCompare
## 图片对比功能  
#### 用法如下  
```php
$service = index::getInstance();
//使用grafika比对 返回数字越小说明越类似
$ret = $service::compareImage($imagePath1,$imagePath2);

//使用图片颜色值比对 返回数字越大说明越类似
$ret1 = $service::compareImageByColor($imagePath1,$imagePath2);

```
