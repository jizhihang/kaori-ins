function conf = voc_config_9084()
	conf.pascal.year = '9084';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9084/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9084/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9084/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9084/Images/%s.txt';
end