function conf = voc_config_9093()
	conf.pascal.year = '9093';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9093/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9093/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9093/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9093/Images/%s.txt';
end