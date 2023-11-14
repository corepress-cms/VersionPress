import { action, computed, observable, makeObservable } from 'mobx';

class LoadingStore {
  progress: number = 100;

  constructor() {
    makeObservable(this, {
      progress: observable,
      isLoading: computed,
      setLoading: action,
      setProgress: action
    });
  }

  get isLoading() {
    return this.progress !== 100;
  }

  setLoading = (isLoading: boolean) => {
    this.progress = isLoading ? 0 : 100;
  };

  setProgress = (progress: ProgressEvent | number) => {
    if (typeof progress === 'number') {
      this.progress = progress;
    } else if (progress.total > 0) {
      this.progress = progress.loaded / progress.total * 100;
    }
  };
}

const loadingStore = new LoadingStore();

export { LoadingStore };
export default loadingStore;
