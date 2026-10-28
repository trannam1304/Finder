<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php'; require_admin();
$pdo = db(); $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
$data = ['id'=>'','category_id'=>'','place_type'=>'food','name'=>'','address'=>'','phone'=>'','latitude'=>'10.73260000','longitude'=>'106.69960000','price_min'=>'0','price_max'=>'0','ticket_price'=>'0','estimated_duration'=>'60','best_time_slot'=>'any','price_level'=>'1','rating'=>'0','rating_count'=>'0','image_url'=>'','open_time'=>'','close_time'=>'','is_24h'=>0,'is_active'=>1,'note'=>''];
if ($id) { $stmt=$pdo->prepare('SELECT * FROM restaurants WHERE id=:id'); $stmt->execute([':id'=>$id]); $found=$stmt->fetch(); if (!$found) { admin_flash('Không tìm thấy địa điểm.', 'error'); header('Location: index.php'); exit; } $data=array_merge($data, $found); }
if (!empty($_SESSION['form_old'])) { $data=array_merge($data,$_SESSION['form_old']); unset($_SESSION['form_old']); }
$categories=$pdo->query('SELECT id,name,icon,type_group FROM categories ORDER BY sort_order,name')->fetchAll(); admin_header($id ? 'Sửa địa điểm' : 'Thêm địa điểm'); ?>
<div class="admin-heading"><div><h1><?= $id ? 'Sửa địa điểm' : 'Thêm địa điểm' ?></h1><p class="admin-note">Quản lý quán ăn, điểm du lịch tham quan và khu vui chơi giải trí. Các trường có dấu * là bắt buộc.</p></div><a class="btn btn--ghost" href="index.php">← Danh sách</a></div>
<form method="post" action="restaurant_save.php" enctype="multipart/form-data" class="admin-form"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$id ?>">
<label class="wide">Tên địa điểm *<input required minlength="2" maxlength="150" class="admin-input" name="name" value="<?= h($data['name']) ?>"></label>
<label>Loại hình địa điểm *<select required class="admin-select" name="place_type" id="place_type">
  <option value="food" <?= ($data['place_type']??'food')==='food'?'selected':'' ?>>🍜 Ẩm thực</option>
  <option value="tourism" <?= ($data['place_type']??'')==='tourism'?'selected':'' ?>>🏛️ Du lịch / Tham quan</option>
  <option value="entertainment" <?= ($data['place_type']??'')==='entertainment'?'selected':'' ?>>🎡 Vui chơi giải trí</option>
</select></label>
<label>Danh mục *<select required class="admin-select" name="category_id" id="category_id"><option value="">Chọn danh mục</option><?php foreach($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" data-group="<?= h($cat['type_group'] ?? 'food') ?>" <?= (int)$data['category_id']===(int)$cat['id']?'selected':'' ?>>[<?= ($cat['type_group'] ?? 'food') === 'tourism' ? 'Du lịch' : (($cat['type_group'] ?? 'food') === 'entertainment' ? 'Vui chơi' : 'Ẩm thực') ?>] <?= h($cat['name']) ?></option><?php endforeach ?></select></label>
<label>Điện thoại<input maxlength="20" class="admin-input" name="phone" value="<?= h($data['phone']) ?>"></label>
<label>Khung giờ lý tưởng<select class="admin-select" name="best_time_slot">
  <option value="any" <?= ($data['best_time_slot']??'any')==='any'?'selected':'' ?>>🌤️ Cả ngày (Linh hoạt)</option>
  <option value="morning" <?= ($data['best_time_slot']??'')==='morning'?'selected':'' ?>>🌅 Buổi sáng</option>
  <option value="afternoon" <?= ($data['best_time_slot']??'')==='afternoon'?'selected':'' ?>>☀️ Buổi chiều</option>
  <option value="evening" <?= ($data['best_time_slot']??'')==='evening'?'selected':'' ?>>🌙 Buổi tối</option>
</select></label>
<label class="wide">Địa chỉ *<input required maxlength="255" class="admin-input" id="address" name="address" value="<?= h($data['address']) ?>"><small>Nhập địa chỉ, rồi tìm trên bản đồ để đặt tọa độ chính xác.</small></label>
<div class="wide actions"><button type="button" id="geocode" class="btn btn--ghost btn--sm">Tìm địa chỉ trên bản đồ</button><span id="map-status" class="admin-note"></span></div>
<div class="wide"><div id="admin-map" class="admin-map" aria-label="Bản đồ chọn tọa độ"></div></div>
<label>Vĩ độ *<input required readonly class="admin-input" id="latitude" name="latitude" value="<?= h((string)$data['latitude']) ?>"></label><label>Kinh độ *<input required readonly class="admin-input" id="longitude" name="longitude" value="<?= h((string)$data['longitude']) ?>"></label>
<label>Giá từ (VND, 0 nếu miễn phí) *<input required min="0" type="number" class="admin-input" name="price_min" value="<?= h((string)$data['price_min']) ?>"></label><label>Giá đến (VND, 0 nếu miễn phí) *<input required min="0" type="number" class="admin-input" name="price_max" value="<?= h((string)$data['price_max']) ?>"></label>
<label>Giá vé vào cổng (VND, 0 = Miễn phí)<input min="0" type="number" class="admin-input" name="ticket_price" value="<?= h((string)($data['ticket_price'] ?? 0)) ?>"></label>
<label>Thời lượng gợi ý (phút) *<input required min="5" max="720" type="number" class="admin-input" name="estimated_duration" value="<?= h((string)($data['estimated_duration'] ?? 60)) ?>"></label>
<label>Mức giá<select class="admin-select" name="price_level"><?php for($i=1;$i<=3;$i++): ?><option value="<?= $i ?>" <?= (int)$data['price_level']===$i?'selected':'' ?>><?= ['','Bình dân / Tiết kiệm','Trung bình','Cao cấp'][$i] ?></option><?php endfor ?></select></label><label>Điểm đánh giá<input class="admin-input" min="0" max="5" step="0.1" type="number" name="rating" value="<?= h((string)$data['rating']) ?>"></label>
<label>Lượt đánh giá<input class="admin-input" min="0" type="number" name="rating_count" value="<?= h((string)$data['rating_count']) ?>"></label><label>Ảnh chính <?php if($data['image_url']): ?><small>Để trống để giữ ảnh hiện tại.</small><?php endif ?><input class="admin-input" type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
<label>Giờ mở cửa<input class="admin-input" type="time" name="open_time" value="<?= h(substr((string)$data['open_time'],0,5)) ?>"></label><label>Giờ đóng cửa<input class="admin-input" type="time" name="close_time" value="<?= h(substr((string)$data['close_time'],0,5)) ?>"></label>
<label class="wide"><span><input type="checkbox" name="is_24h" value="1" <?= !empty($data['is_24h'])?'checked':'' ?>> Mở cửa 24/7 (Không giới hạn giờ)</span></label><label class="wide">Ghi chú / Mô tả ngắn<textarea class="admin-input" name="note" maxlength="255" rows="3"><?= h($data['note']) ?></textarea></label>
<div class="wide actions"><button class="btn btn--primary" type="submit">Lưu địa điểm</button><a class="btn btn--ghost" href="index.php">Hủy</a></div></form>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script>
const latInput=document.getElementById('latitude'),lngInput=document.getElementById('longitude'),status=document.getElementById('map-status');let lat=Number(latInput.value),lng=Number(lngInput.value);const map=L.map('admin-map').setView([lat,lng],15);L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',{maxZoom:19,subdomains:'abcd',attribution:'© OpenStreetMap © CARTO'}).addTo(map);let marker=L.marker([lat,lng],{draggable:true}).addTo(map);function setPoint(point){latInput.value=Number(point.lat).toFixed(8);lngInput.value=Number(point.lng).toFixed(8);marker.setLatLng(point);map.panTo(point)} marker.on('dragend',()=>setPoint(marker.getLatLng()));map.on('click',e=>setPoint(e.latlng));document.getElementById('geocode').addEventListener('click',async()=>{const q=document.getElementById('address').value.trim();if(q.length<3){status.textContent='Nhập ít nhất 3 ký tự địa chỉ.';return}status.textContent='Đang tìm...';try{const r=await fetch('../api/geocode.php?q='+encodeURIComponent(q));const j=await r.json();if(!r.ok||!j.data?.length)throw Error();setPoint(j.data[0]);status.textContent='Đã đặt tọa độ. Bạn có thể kéo marker để tinh chỉnh.'}catch{status.textContent='Không tìm được địa chỉ. Hãy đặt điểm thủ công trên bản đồ.'}});
</script><?php admin_footer();

